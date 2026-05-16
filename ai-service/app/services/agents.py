from __future__ import annotations

import math
from dataclasses import dataclass, field
from typing import Any

from app.schemas.matching import (
    CandidatePayload,
    CandidateProfile,
    JobPayload,
    JobProfile,
)
from app.services.extractor import ExtractionService
from app.services.normalizer import (
    DomainClassifier,
    RelatedMatch,
    SeniorityNormalizer,
    SkillNormalizer,
    SkillRelationGraph,
)


@dataclass
class ExtractorAgent:
    """Async agent that produces structured CandidateProfile + JobProfile."""

    _service: ExtractionService = None  # type: ignore[assignment]

    def __post_init__(self) -> None:
        self._service = ExtractionService()

    @property
    def provider_name(self) -> str:
        """Active LLM provider name for trace metadata."""
        return self._service.provider_name

    async def run(
        self,
        candidate: CandidatePayload,
        job: JobPayload,
    ) -> tuple[CandidateProfile, JobProfile]:
        c_profile, j_profile = await __import__("asyncio").gather(
            self._service.extract_candidate(candidate),
            self._service.extract_job(job),
        )
        return c_profile, j_profile


@dataclass
class RAGAgent:
    """Retrieves grounding evidence from DB-backed knowledge corpus (pgvector).

    Falls back to static in-memory corpus when DB or embeddings are unavailable.
    """

    def __post_init__(self) -> None:
        from app.services.retriever import KnowledgeRetriever

        self._retriever = KnowledgeRetriever()

    async def run(
        self, job_profile: JobProfile, job_raw: dict[str, Any],
    ) -> tuple[list[dict[str, Any]], str]:
        """Returns (evidence_list, retrieval_method)."""
        # Build query from job profile
        query_parts: list[str] = []
        if job_raw.get("title"):
            query_parts.append(job_raw["title"])
        if job_profile.seniority:
            query_parts.append(job_profile.seniority)
        query_parts.extend(job_profile.required_skills[:5])
        query_parts.extend(job_profile.domain_keywords[:5])
        query = " ".join(filter(None, query_parts))

        if not query.strip():
            query = "software engineering hiring evaluation"

        return await self._retriever.retrieve(query)


# ---------------------------------------------------------------------------
# Default scoring weights (canonical from AI_MATCH_CONTRACTS.md)
# ---------------------------------------------------------------------------

_DEFAULT_WEIGHTS: dict[str, float] = {
    "required_skill_coverage": 0.40,
    "preferred_skill_coverage": 0.15,
    "experience_fit": 0.15,
    "seniority_fit": 0.10,
    "domain_relevance": 0.10,
    "confidence_adjustment": 0.10,
}

# Partial credit multiplier for related-skill matches.
# A related match with similarity 0.60 contributes 0.60 * _RELATED_CREDIT
# towards skill coverage.  Kept below 1.0 so related matches are always
# worth less than exact/synonym matches.
_RELATED_CREDIT = 0.80

# Two-hop matches get halved credit relative to one-hop (locked constraint).
# This ensures: exact/synonym > one-hop related > two-hop indirect
_TWO_HOP_RELATED_CREDIT = _RELATED_CREDIT * 0.50  # = 0.40


@dataclass
class MatcherAgent:
    """Hybrid matcher: weighted multi-factor scoring with graph-lite related-skill support.

    Components (default weight):
      1. Required skill coverage  (40%)  — exact + synonym + related (partial)
      2. Preferred skill coverage  (15%) — exact + synonym + related (partial)
      3. Experience fit            (15%)
      4. Seniority fit             (10%)
      5. Domain relevance          (10%)
      6. Confidence adjustment     (10%)

    Weights can be overridden via job.scoring_config.
    """

    _SENIORITY_LEVELS: list[str] = field(
        default_factory=lambda: [
            "intern", "fresher", "junior", "mid", "senior", "lead", "principal",
        ],
    )

    def __post_init__(self) -> None:
        self._skill_norm = SkillNormalizer()
        self._seniority_norm = SeniorityNormalizer()
        self._domain_cls = DomainClassifier()
        self._skill_graph = SkillRelationGraph()

    def run(
        self,
        c_profile: CandidateProfile,
        j_profile: JobProfile,
        scoring_config: dict[str, Any] | None = None,
        job_title: str = "",
    ) -> dict[str, Any]:
        # ── Step 1: Exact/synonym matching ─────────────────────────────
        matched_required, missing_required_raw = self._skill_norm.match_skills(
            c_profile.skills, j_profile.required_skills,
        )
        matched_preferred, missing_preferred_raw = self._skill_norm.match_skills(
            c_profile.skills, j_profile.preferred_skills,
        )

        # ── Step 2: Graph-lite related matching for remaining gaps ──────
        related_req, truly_missing_req = self._skill_graph.find_related_matches(
            c_profile.skills, missing_required_raw,
        )
        related_pref, truly_missing_pref = self._skill_graph.find_related_matches(
            c_profile.skills, missing_preferred_raw,
        )

        # Normalized counts for scoring
        n_required = len(set(self._skill_norm.normalize_skills(j_profile.required_skills)))
        n_preferred = len(set(self._skill_norm.normalize_skills(j_profile.preferred_skills)))

        risk_flags: list[str] = []

        # ── Component 1: Required skill coverage (exact + related partial) ──
        exact_req_credit = float(len(matched_required))
        related_req_credit = sum(
            r.similarity * (_RELATED_CREDIT if r.hop_count == 1 else _TWO_HOP_RELATED_CREDIT)
            for r in related_req
        )
        if n_required > 0:
            req_coverage = min(1.0, (exact_req_credit + related_req_credit) / n_required)
        else:
            req_coverage = 0.5

        if n_required > 0 and req_coverage < 0.5:
            missing_pct = int((1 - req_coverage) * 100)
            risk_flags.append(
                f"Thiếu >{missing_pct}% kỹ năng bắt buộc ({len(truly_missing_req)}/{n_required})"
            )

        one_hop_req = [r for r in related_req if r.hop_count == 1]
        two_hop_req = [r for r in related_req if r.hop_count == 2]
        if one_hop_req:
            risk_flags.append(
                f"Có {len(one_hop_req)} kỹ năng bắt buộc phù hợp gián tiếp (one-hop)"
            )
        if two_hop_req:
            risk_flags.append(
                f"Có {len(two_hop_req)} kỹ năng bắt buộc phù hợp rất gián tiếp (two-hop, tín nhiệm thấp)"
            )

        # ── Component 2: Preferred skill coverage ──
        exact_pref_credit = float(len(matched_preferred))
        related_pref_credit = sum(
            r.similarity * (_RELATED_CREDIT if r.hop_count == 1 else _TWO_HOP_RELATED_CREDIT)
            for r in related_pref
        )
        if n_preferred > 0:
            pref_coverage = min(1.0, (exact_pref_credit + related_pref_credit) / n_preferred)
        else:
            pref_coverage = 0.5

        # ── Component 3: Experience fit ──
        exp_score, exp_detail, exp_flags = self._score_experience(c_profile, j_profile)
        risk_flags.extend(exp_flags)

        # ── Component 4: Seniority fit ──
        sen_score, sen_detail, sen_flags = self._score_seniority(c_profile, j_profile)
        risk_flags.extend(sen_flags)

        # ── Component 5: Domain relevance (with title-based enrichment) ──
        dom_score, dom_detail = self._score_domain_relevance(c_profile, j_profile, job_title)

        # ── Component 6: Confidence adjustment ──
        conf_map = {"high": 1.0, "medium": 0.7, "low": 0.4}
        c_conf = conf_map.get(c_profile.extraction_confidence, 0.4)
        j_conf = conf_map.get(j_profile.extraction_confidence, 0.4)
        avg_conf = (c_conf + j_conf) / 2

        if c_profile.extraction_method == "fallback":
            risk_flags.append(
                "Hồ sơ ứng viên được trích xuất bằng heuristic — độ chính xác có thể thấp hơn"
            )
        if j_profile.extraction_method == "fallback":
            risk_flags.append(
                "JD được trích xuất bằng heuristic — độ chính xác có thể thấp hơn"
            )

        # Confidence label — penalize if many matches are indirect
        # Two-hop matches penalize confidence more than one-hop
        total_req_matches = len(matched_required) + len(related_req)
        one_hop_count = sum(1 for r in related_req if r.hop_count == 1)
        two_hop_count = sum(1 for r in related_req if r.hop_count == 2)
        indirect_ratio = len(related_req) / max(1, total_req_matches) if total_req_matches else 0
        two_hop_penalty = (two_hop_count / max(1, total_req_matches)) * 0.10
        conf_penalty = indirect_ratio * 0.15 + two_hop_penalty
        adjusted_avg_conf = max(0.0, avg_conf - conf_penalty)

        if adjusted_avg_conf >= 0.85:
            confidence_label = "high"
        elif adjusted_avg_conf >= 0.55:
            confidence_label = "medium"
        else:
            confidence_label = "low"

        # ── Resolve weights ──
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

        # ── Build detail strings ──
        req_detail_parts = [f"{len(matched_required)} exact"]
        if related_req:
            req_detail_parts.append(f"{len(related_req)} related")
        req_detail_parts.append(f"/ {n_required} bắt buộc")
        req_detail = " + ".join(req_detail_parts) if n_required else "không có yêu cầu kỹ năng"

        pref_detail_parts = [f"{len(matched_preferred)} exact"]
        if related_pref:
            pref_detail_parts.append(f"{len(related_pref)} related")
        pref_detail_parts.append(f"/ {n_preferred} ưu tiên")
        pref_detail = " + ".join(pref_detail_parts) if n_preferred else "không có kỹ năng ưu tiên"

        scores = {
            "required_skill_coverage": req_coverage,
            "preferred_skill_coverage": pref_coverage,
            "experience_fit": exp_score,
            "seniority_fit": sen_score,
            "domain_relevance": dom_score,
            "confidence_adjustment": adjusted_avg_conf,
        }
        details = {
            "required_skill_coverage": req_detail,
            "preferred_skill_coverage": pref_detail,
            "experience_fit": exp_detail,
            "seniority_fit": sen_detail,
            "domain_relevance": dom_detail,
            "confidence_adjustment": f"extraction confidence: {confidence_label}",
        }

        score_breakdown: dict[str, dict[str, Any]] = {}
        total = 0.0
        for key, weight in weights.items():
            raw = scores[key]
            weighted = round(raw * weight * 100, 2)
            total += weighted
            score_breakdown[key] = {
                "score": round(raw, 4),
                "weight": round(weight, 4),
                "weighted": weighted,
                "detail": details[key],
            }

        fit_score = round(min(98.0, total), 2)

        # ── Build output ──
        # matched_skills: exact + synonym matches
        # missing_skills: truly missing after considering related matches
        # related_matches: structured list for recruiter explanation
        return {
            "fit_score": fit_score,
            "score_breakdown": score_breakdown,
            "matched_skills": matched_required + matched_preferred,
            "missing_skills": truly_missing_req,
            "missing_preferred_skills": truly_missing_pref,
            "related_matches": [
                {
                    "candidate_skill": r.candidate_skill,
                    "target_skill": r.target_skill,
                    "relation_type": r.relation_type,
                    "similarity": r.similarity,
                    "hop_count": r.hop_count,
                    "via_skill": r.via_skill,
                }
                for r in (related_req + related_pref)
            ],
            "risk_flags": risk_flags,
            "confidence_label": confidence_label,
        }

    # ------------------------------------------------------------------
    # Scoring helpers
    # ------------------------------------------------------------------

    def _score_experience(
        self, c: CandidateProfile, j: JobProfile,
    ) -> tuple[float, str, list[str]]:
        """Score experience fit using Gaussian proximity."""
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
            return 0.5, f"kinh nghiệm ứng viên không rõ vs {range_str}", [
                f"Kinh nghiệm ứng viên không rõ — không thể đánh giá (yêu cầu {range_str})",
            ]

        mid = ((j_min or 0) + (j_max or j_min or 0)) / 2
        spread = max(1.0, ((j_max or mid) - (j_min or mid)) / 2) if (j_max and j_min) else 2.0

        distance = abs(c_exp - mid)
        score = math.exp(-0.5 * (distance / spread) ** 2)

        range_str = f"{j_min or '?'}-{j_max or '?'} năm"
        detail = f"{c_exp} năm vs yêu cầu {range_str}"

        if score < 0.5:
            flags.append(
                f"Khoảng cách kinh nghiệm: ứng viên {c_exp} năm, yêu cầu {range_str}"
            )

        return round(score, 4), detail, flags

    def _score_seniority(
        self, c: CandidateProfile, j: JobProfile,
    ) -> tuple[float, str, list[str]]:
        """Score seniority match using SeniorityNormalizer."""
        c_sen = self._seniority_norm.normalize(c.seniority)
        j_sen = self._seniority_norm.normalize(j.seniority)

        if not c_sen and c.experience_years is not None:
            c_sen = self._seniority_norm.infer_from_experience(c.experience_years)

        if not c_sen or not j_sen:
            if not c_sen and not j_sen:
                return 0.5, "không có dữ liệu cấp bậc", []
            if not c_sen:
                return 0.5, "cấp bậc ứng viên không rõ", []
            return 0.5, "JD không chỉ định cấp bậc", []

        gap = self._seniority_norm.gap(c_sen, j_sen)
        if gap is None:
            return 0.5, f"{c_sen} vs {j_sen} (unmapped)", []

        flag_msg = f"Khoảng cách cấp bậc: ứng viên {c_sen}, yêu cầu {j_sen}"

        if gap == 0:
            return 1.0, f"cùng cấp bậc {c_sen}", []
        elif gap == 1:
            return 0.7, f"{c_sen} vs {j_sen} (1 bậc)", [flag_msg]
        elif gap == 2:
            return 0.3, f"{c_sen} vs {j_sen} (2 bậc)", [flag_msg]
        else:
            return 0.0, f"{c_sen} vs {j_sen} ({gap} bậc)", [flag_msg]

    def _score_domain_relevance(
        self, c: CandidateProfile, j: JobProfile, job_title: str = "",
    ) -> tuple[float, str]:
        """Score domain keyword overlap with title-based enrichment."""
        c_kw = {k.lower() for k in c.domain_keywords}
        j_kw_list = list(j.domain_keywords)

        if job_title:
            j_kw_list = self._domain_cls.enrich_domain_keywords(job_title, j_kw_list)

        j_kw = {k.lower() for k in j_kw_list}

        if not j_kw:
            return 0.5, "không có domain keywords"

        overlap = c_kw & j_kw
        score = len(overlap) / len(j_kw)
        return round(score, 4), f"{len(overlap)}/{len(j_kw)} keywords"


@dataclass
class ExplainerAgent:
    """Generates human-readable rationale from matching results.

    Supports related-skill match explanations for recruiter transparency.
    """

    def run(
        self,
        matching: dict[str, Any],
        evidence: list[dict[str, Any]],
        c_profile: CandidateProfile,
        j_profile: JobProfile,
    ) -> list[str]:
        reasons: list[str] = []

        matched = matching["matched_skills"]
        missing = matching["missing_skills"]
        missing_pref = matching.get("missing_preferred_skills", [])
        related = matching.get("related_matches", [])

        # Exact/synonym matches
        reasons.append(
            f"Matched {len(matched)} skills (exact/synonym): "
            f"{', '.join(matched[:6]) or 'none'}."
        )

        # Related matches — explain each with provenance (4-tier)
        one_hop = [r for r in related if r.get("hop_count", 1) == 1]
        two_hop = [r for r in related if r.get("hop_count", 1) == 2]

        if one_hop:
            reasons.append(
                f"Found {len(one_hop)} one-hop related match(es) (partial credit):"
            )
            for r in one_hop[:4]:
                relation_label = _relation_label(r["relation_type"])
                sim_pct = int(r["similarity"] * 100)
                reasons.append(
                    f"  • {r['candidate_skill']} → {r['target_skill']} "
                    f"({relation_label}, {sim_pct}% match)"
                )

        if two_hop:
            reasons.append(
                f"Found {len(two_hop)} two-hop indirect match(es) (lower confidence):"
            )
            for r in two_hop[:3]:
                via = r.get("via_skill", "?")
                sim_pct = int(r["similarity"] * 100)
                reasons.append(
                    f"  • {r['candidate_skill']} → {via} → {r['target_skill']} "
                    f"(indirect, {sim_pct}% match)"
                )

        if missing:
            reasons.append(f"Truly missing required skills: {', '.join(missing[:6])}.")
        if missing_pref:
            reasons.append(
                f"Missing preferred (nice-to-have): {', '.join(missing_pref[:4])}."
            )

        if c_profile.seniority and j_profile.seniority:
            if c_profile.seniority == j_profile.seniority:
                reasons.append(f"Seniority matches: both {c_profile.seniority}-level.")
            else:
                reasons.append(
                    f"Seniority gap: candidate is {c_profile.seniority}, "
                    f"role expects {j_profile.seniority}."
                )

        if c_profile.experience_years is not None:
            reasons.append(f"Estimated experience: {c_profile.experience_years} year(s).")

        if evidence:
            method = evidence[0].get("retrieval_method", "unknown")
            reasons.append(
                f"Grounded by evidence source: \"{evidence[0]['source']}\" (via {method})."
            )

        if c_profile.extraction_method == "fallback":
            reasons.append(
                "Note: Candidate profile extracted via keyword heuristics (LLM unavailable). "
                "Accuracy may be lower."
            )

        return reasons


def _relation_label(relation_type: str) -> str:
    """Human-readable label for relation types (including two-hop composites)."""
    labels = {
        "framework_of": "framework of",
        "prerequisite": "prerequisite for",
        "same_ecosystem": "same ecosystem",
        "alternative_to": "alternative to",
        "related_tooling": "related tooling",
        "adjacent_skill": "adjacent skill",
        "superset_of": "superset of",
    }
    # Two-hop composite types contain "→" (e.g. "prerequisite→framework_of")
    if "→" in relation_type:
        return "indirect relation"
    return labels.get(relation_type, relation_type)


@dataclass
class CriticAgent:
    def run(self, fit_score: float, reasoning: list[str]) -> tuple[float, list[str]]:
        notes = []
        adjusted = fit_score
        if fit_score > 90 and len(reasoning) < 2:
            adjusted = 85.0
            notes.append("Score reduced: explanation depth insufficient to justify high fit.")
        if fit_score < 40:
            notes.append("Low fit score — recommend human review before rejection.")
        return adjusted, notes
