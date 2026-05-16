from __future__ import annotations

from datetime import datetime, timezone

from app.schemas.matching import (
    EvidenceItem,
    MatchRequest,
    MatchResponse,
    ScoreBreakdownItem,
)
from app.services.agents import (
    CriticAgent,
    ExplainerAgent,
    ExtractorAgent,
    MatcherAgent,
    RAGAgent,
)
from app.services.feedback_reranker import FeedbackReranker


class MatchOrchestrator:
    def __init__(self) -> None:
        self.extractor = ExtractorAgent()
        self.rag = RAGAgent()
        self.matcher = MatcherAgent()
        self.explainer = ExplainerAgent()
        self.critic = CriticAgent()
        self.feedback_reranker = FeedbackReranker()

    async def run(self, payload: MatchRequest) -> MatchResponse:
        trace: list[str] = []

        # --- Structured extraction (structured-first for jobs, LLM-first for candidates) ---
        c_profile, j_profile = await self.extractor.run(payload.candidate, payload.job)
        trace.append(
            f"ExtractorAgent: CandidateProfile ({c_profile.extraction_method}, "
            f"conf={c_profile.extraction_confidence}), JobProfile "
            f"({j_profile.extraction_method}, conf={j_profile.extraction_confidence}), "
            f"provider={self.extractor.provider_name}"
        )

        # --- RAG grounding ---
        evidence, retrieval_method = await self.rag.run(j_profile, payload.job.model_dump())
        trace.append(
            f"RAGAgent: retrieved {len(evidence)} evidence docs via {retrieval_method}"
        )

        # --- Hybrid multi-factor matching (with optional scoring_config override) ---
        scoring_config = payload.job.scoring_config if payload.job.scoring_config else None
        matching = self.matcher.run(
            c_profile, j_profile,
            scoring_config=scoring_config,
            job_title=payload.job.title,
        )
        one_hop_count = sum(
            1 for r in matching.get("related_matches", [])
            if r.get("hop_count", 1) == 1
        )
        two_hop_count = sum(
            1 for r in matching.get("related_matches", [])
            if r.get("hop_count", 1) == 2
        )
        trace.append(
            f"MatcherAgent: fit_score={matching['fit_score']}, "
            f"matched={len(matching['matched_skills'])}, "
            f"related={one_hop_count}×1hop+{two_hop_count}×2hop, "
            f"missing={len(matching['missing_skills'])}, "
            f"confidence={matching['confidence_label']}"
        )

        reasons = self.explainer.run(matching, evidence, c_profile, j_profile)
        trace.append("ExplainerAgent: generated citation-aware rationale")

        final_score, critic_notes = self.critic.run(matching["fit_score"], reasons)
        trace.append("CriticAgent: validated confidence and adjusted edge cases")

        # --- Feedback-derived reranking (Phase 15) ---
        # NOTE: This does NOT overwrite final_score. It produces a separate
        # adjustment that callers can optionally display.
        feedback_adj = await self.feedback_reranker.adjust(
            job_id=payload.job.id,
            fit_score=final_score,
            confidence_label=matching.get("confidence_label", "medium"),
        )
        feedback_adj_dict = feedback_adj.to_dict()
        if feedback_adj.adjustment_points != 0:
            trace.append(
                f"FeedbackReranker: adjustment={feedback_adj.adjustment_points:+.1f}pts "
                f"({feedback_adj.reason})"
            )
        else:
            trace.append(
                f"FeedbackReranker: no adjustment ({feedback_adj.reason})"
            )

        combined_reasoning = reasons + critic_notes
        rank = (
            "high_fit" if final_score >= 80
            else "medium_fit" if final_score >= 60
            else "low_fit"
        )

        # Build score breakdown with Pydantic models
        score_breakdown = {
            k: ScoreBreakdownItem(**v)
            for k, v in matching["score_breakdown"].items()
        }

        return MatchResponse(
            candidate_id=payload.candidate.id,
            job_id=payload.job.id,
            fit_score=final_score,  # canonical deterministic score — NEVER overwritten
            rank_label=rank,
            matched_skills=matching["matched_skills"],
            missing_skills=matching["missing_skills"],
            missing_preferred_skills=matching.get("missing_preferred_skills", []),
            score_breakdown=score_breakdown,
            risk_flags=matching.get("risk_flags", []),
            confidence_label=matching.get("confidence_label", "medium"),
            reasoning=combined_reasoning,
            evidence=[EvidenceItem(**item) for item in evidence],
            retrieval_method=retrieval_method,
            agent_trace=trace,
            candidate_profile=c_profile,
            job_profile=j_profile,
            feedback_adjustment=feedback_adj_dict,
            pipeline_version="v2.0",
            generated_at=datetime.now(timezone.utc).isoformat(),
        )
