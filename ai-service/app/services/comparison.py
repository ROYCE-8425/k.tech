"""
AI Decision Lab — Comparison runner (Phase 19).

Runs the MatcherAgent in 4 controlled modes against the same extracted profiles:

  1. baseline      — exact/synonym matching only (no graph reasoning)
  2. graph_1hop    — exact/synonym + one-hop related skills
  3. graph_2hop    — exact/synonym + one-hop + two-hop related skills (= full pipeline)
  4. feedback_aware — graph_2hop + feedback reranker adjustment

This is diagnostic/interpretive — it does NOT overwrite canonical fit_score.
Each mode produces a compact result for side-by-side comparison.
"""
from __future__ import annotations

import math
from dataclasses import dataclass
from typing import Any

from app.schemas.matching import CandidateProfile, JobProfile
from app.services.agents import (
    _DEFAULT_WEIGHTS,
    _RELATED_CREDIT,
    _TWO_HOP_RELATED_CREDIT,
)
from app.services.normalizer import (
    DomainClassifier,
    SeniorityNormalizer,
    SkillNormalizer,
    SkillRelationGraph,
)


@dataclass
class ModeResult:
    """Result for a single comparison mode."""
    mode: str
    label: str
    description: str
    fit_score: float
    rank_label: str
    confidence_label: str
    matched_skills: list[str]
    missing_skills: list[str]
    related_matches_count: int
    one_hop_count: int
    two_hop_count: int
    score_breakdown: dict[str, Any]

    def to_dict(self) -> dict[str, Any]:
        return {
            "mode": self.mode,
            "label": self.label,
            "description": self.description,
            "fit_score": self.fit_score,
            "rank_label": self.rank_label,
            "confidence_label": self.confidence_label,
            "matched_skills": self.matched_skills,
            "missing_skills": self.missing_skills,
            "related_matches_count": self.related_matches_count,
            "one_hop_count": self.one_hop_count,
            "two_hop_count": self.two_hop_count,
            "score_breakdown": self.score_breakdown,
        }


class ComparisonRunner:
    """Runs the matcher in controlled comparison modes.

    Reuses the same deterministic scoring logic as MatcherAgent
    but with selective graph reasoning to show impact at each layer.
    """

    MODE_DEFINITIONS = [
        {
            "mode": "baseline",
            "label": "Baseline (Exact/Synonym)",
            "description": "Chỉ so khớp từ khóa và đồng nghĩa — không có graph reasoning",
            "allow_1hop": False,
            "allow_2hop": False,
        },
        {
            "mode": "graph_1hop",
            "label": "Graph One-Hop",
            "description": "Thêm kỹ năng liên quan trực tiếp (framework_of, same_ecosystem, ...)",
            "allow_1hop": True,
            "allow_2hop": False,
        },
        {
            "mode": "graph_2hop",
            "label": "Graph Two-Hop (Full)",
            "description": "Thêm kỹ năng liên quan gián tiếp qua chuỗi 2 bước",
            "allow_1hop": True,
            "allow_2hop": True,
        },
        {
            "mode": "feedback_aware",
            "label": "Feedback-Aware",
            "description": "Graph Two-Hop + điều chỉnh từ phản hồi recruiter (bounded ±3/−5)",
            "allow_1hop": True,
            "allow_2hop": True,
        },
    ]

    def __init__(self) -> None:
        self._skill_norm = SkillNormalizer()
        self._seniority_norm = SeniorityNormalizer()
        self._domain_cls = DomainClassifier()
        self._skill_graph = SkillRelationGraph()

    async def run_comparison(
        self,
        c_profile: CandidateProfile,
        j_profile: JobProfile,
        job_title: str = "",
        job_id: int = 0,
        scoring_config: dict[str, Any] | None = None,
    ) -> list[dict[str, Any]]:
        """Run all 4 comparison modes and return results."""
        results: list[dict[str, Any]] = []

        # Pre-compute shared data (exact/synonym matches + graph matches)
        matched_required, missing_required_raw = self._skill_norm.match_skills(
            c_profile.skills, j_profile.required_skills,
        )
        matched_preferred, missing_preferred_raw = self._skill_norm.match_skills(
            c_profile.skills, j_profile.preferred_skills,
        )

        # Full graph-lite related matching
        all_related_req, truly_missing_req = self._skill_graph.find_related_matches(
            c_profile.skills, missing_required_raw,
        )
        all_related_pref, truly_missing_pref = self._skill_graph.find_related_matches(
            c_profile.skills, missing_preferred_raw,
        )

        # Pre-compute shared scoring components
        exp_score, exp_detail, _ = self._score_experience(c_profile, j_profile)
        sen_score, sen_detail, _ = self._score_seniority(c_profile, j_profile)
        dom_score, dom_detail = self._score_domain_relevance(c_profile, j_profile, job_title)

        conf_map = {"high": 1.0, "medium": 0.7, "low": 0.4}
        c_conf = conf_map.get(c_profile.extraction_confidence, 0.4)
        j_conf = conf_map.get(j_profile.extraction_confidence, 0.4)
        base_avg_conf = (c_conf + j_conf) / 2

        n_required = len(set(self._skill_norm.normalize_skills(j_profile.required_skills)))
        n_preferred = len(set(self._skill_norm.normalize_skills(j_profile.preferred_skills)))

        weights = dict(_DEFAULT_WEIGHTS)
        if scoring_config and isinstance(scoring_config, dict):
            custom_weights = scoring_config.get("weights")
            if isinstance(custom_weights, dict):
                for key in weights:
                    if key in custom_weights:
                        try:
                            weights[key] = float(custom_weights[key])
                        except (ValueError, TypeError):
                            pass
                total_w = sum(weights.values())
                if total_w > 0 and abs(total_w - 1.0) > 0.001:
                    weights = {k: v / total_w for k, v in weights.items()}

        # Get feedback adjustment for feedback_aware mode
        feedback_adj_points = 0.0
        feedback_reason = ""
        try:
            from app.services.feedback_reranker import FeedbackReranker
            reranker = FeedbackReranker()
            if reranker.enabled and job_id:
                adj = await reranker.adjust(
                    job_id=job_id,
                    fit_score=0,  # placeholder — we compute per-mode
                    confidence_label="medium",
                )
                feedback_adj_points = adj.adjustment_points
                feedback_reason = adj.reason
        except Exception:
            pass

        # Run each mode
        for mode_def in self.MODE_DEFINITIONS:
            mode = mode_def["mode"]
            allow_1hop = mode_def["allow_1hop"]
            allow_2hop = mode_def["allow_2hop"]

            # Filter related matches by hop count
            if not allow_1hop:
                related_req = []
                related_pref = []
            elif not allow_2hop:
                related_req = [r for r in all_related_req if r.hop_count == 1]
                related_pref = [r for r in all_related_pref if r.hop_count == 1]
            else:
                related_req = list(all_related_req)
                related_pref = list(all_related_pref)

            # Compute missing skills for this mode
            if related_req:
                related_target_skills = {r.target_skill.lower() for r in related_req}
                mode_missing_req = [s for s in missing_required_raw if s.lower() not in related_target_skills]
            else:
                mode_missing_req = list(missing_required_raw)

            if related_pref:
                related_target_pref = {r.target_skill.lower() for r in related_pref}
                mode_missing_pref = [s for s in missing_preferred_raw if s.lower() not in related_target_pref]
            else:
                mode_missing_pref = list(missing_preferred_raw)

            # Component 1: Required skill coverage
            exact_req_credit = float(len(matched_required))
            related_req_credit = sum(
                r.similarity * (_RELATED_CREDIT if r.hop_count == 1 else _TWO_HOP_RELATED_CREDIT)
                for r in related_req
            )
            req_coverage = min(1.0, (exact_req_credit + related_req_credit) / n_required) if n_required > 0 else 0.5

            # Component 2: Preferred skill coverage
            exact_pref_credit = float(len(matched_preferred))
            related_pref_credit = sum(
                r.similarity * (_RELATED_CREDIT if r.hop_count == 1 else _TWO_HOP_RELATED_CREDIT)
                for r in related_pref
            )
            pref_coverage = min(1.0, (exact_pref_credit + related_pref_credit) / n_preferred) if n_preferred > 0 else 0.5

            # Confidence with indirect-match penalty
            total_req_matches = len(matched_required) + len(related_req)
            indirect_ratio = len(related_req) / max(1, total_req_matches) if total_req_matches else 0
            two_hop_count = sum(1 for r in related_req if r.hop_count == 2)
            two_hop_penalty = (two_hop_count / max(1, total_req_matches)) * 0.10 if total_req_matches else 0
            conf_penalty = indirect_ratio * 0.15 + two_hop_penalty
            adjusted_conf = max(0.0, base_avg_conf - conf_penalty)

            if adjusted_conf >= 0.85:
                confidence_label = "high"
            elif adjusted_conf >= 0.55:
                confidence_label = "medium"
            else:
                confidence_label = "low"

            # Build scores
            scores = {
                "required_skill_coverage": req_coverage,
                "preferred_skill_coverage": pref_coverage,
                "experience_fit": exp_score,
                "seniority_fit": sen_score,
                "domain_relevance": dom_score,
                "confidence_adjustment": adjusted_conf,
            }

            # Compute total
            score_breakdown = {}
            total = 0.0
            for key, weight in weights.items():
                raw = scores[key]
                weighted = round(raw * weight * 100, 2)
                total += weighted
                score_breakdown[key] = {
                    "score": round(raw, 4),
                    "weight": round(weight, 4),
                    "weighted": weighted,
                }

            fit_score = round(min(98.0, total), 2)

            # Apply feedback adjustment for feedback_aware mode only
            display_score = fit_score
            if mode == "feedback_aware" and feedback_adj_points != 0:
                display_score = round(min(98.0, max(0.0, fit_score + feedback_adj_points)), 2)
                score_breakdown["feedback_adjustment"] = {
                    "points": feedback_adj_points,
                    "reason": feedback_reason,
                }

            rank = (
                "high_fit" if display_score >= 80
                else "medium_fit" if display_score >= 60
                else "low_fit"
            )

            one_hop_cnt = sum(1 for r in related_req if r.hop_count == 1)
            two_hop_cnt = sum(1 for r in related_req if r.hop_count == 2)

            result = ModeResult(
                mode=mode,
                label=mode_def["label"],
                description=mode_def["description"],
                fit_score=display_score,
                rank_label=rank,
                confidence_label=confidence_label,
                matched_skills=matched_required + matched_preferred,
                missing_skills=mode_missing_req,
                related_matches_count=len(related_req) + len(related_pref),
                one_hop_count=one_hop_cnt,
                two_hop_count=two_hop_cnt,
                score_breakdown=score_breakdown,
            )
            results.append(result.to_dict())

        return results

    # ------------------------------------------------------------------
    # Scoring helpers (copied from MatcherAgent for isolation)
    # ------------------------------------------------------------------

    def _score_experience(
        self, c: CandidateProfile, j: JobProfile,
    ) -> tuple[float, str, list[str]]:
        flags: list[str] = []
        c_exp = c.experience_years
        j_min = j.min_experience_years
        j_max = j.max_experience_years

        if c_exp is None and j_min is None and j_max is None:
            return 0.5, "không có dữ liệu kinh nghiệm", []
        if j_min is None and j_max is None:
            return 0.5, f"ứng viên {c_exp} năm, JD không chỉ định", []
        if c_exp is None:
            range_str = f"{j_min or '?'}-{j_max or '?'} năm"
            return 0.5, f"kinh nghiệm ứng viên không rõ vs {range_str}", []

        mid = ((j_min or 0) + (j_max or j_min or 0)) / 2
        spread = max(1.0, ((j_max or mid) - (j_min or mid)) / 2) if (j_max and j_min) else 2.0
        distance = abs(c_exp - mid)
        score = math.exp(-0.5 * (distance / spread) ** 2)

        range_str = f"{j_min or '?'}-{j_max or '?'} năm"
        detail = f"{c_exp} năm vs yêu cầu {range_str}"
        return round(score, 4), detail, flags

    def _score_seniority(
        self, c: CandidateProfile, j: JobProfile,
    ) -> tuple[float, str, list[str]]:
        c_sen = self._seniority_norm.normalize(c.seniority)
        j_sen = self._seniority_norm.normalize(j.seniority)

        if not c_sen and c.experience_years is not None:
            c_sen = self._seniority_norm.infer_from_experience(c.experience_years)

        if not c_sen or not j_sen:
            return 0.5, "không có dữ liệu cấp bậc", []

        gap = self._seniority_norm.gap(c_sen, j_sen)
        if gap is None:
            return 0.5, f"{c_sen} vs {j_sen} (unmapped)", []

        if gap == 0:
            return 1.0, f"cùng cấp bậc {c_sen}", []
        elif gap == 1:
            return 0.7, f"{c_sen} vs {j_sen} (1 bậc)", []
        elif gap == 2:
            return 0.3, f"{c_sen} vs {j_sen} (2 bậc)", []
        else:
            return 0.0, f"{c_sen} vs {j_sen} ({gap} bậc)", []

    def _score_domain_relevance(
        self, c: CandidateProfile, j: JobProfile, job_title: str = "",
    ) -> tuple[float, str]:
        c_kw = {k.lower() for k in c.domain_keywords}
        j_kw_list = list(j.domain_keywords)
        if job_title:
            j_kw_list = self._domain_cls.enrich_domain_keywords(job_title, j_kw_list)
        j_kw = {k.lower() for k in j_kw_list}
        if not j_kw:
            return 0.5, "không có domain keywords"
        overlap = c_kw & j_kw
        return round(len(overlap) / len(j_kw), 4), f"{len(overlap)}/{len(j_kw)} keywords"
