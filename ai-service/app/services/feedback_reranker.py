"""
Feedback-derived reranking foundation (Phase 15).

Lightweight optional adjustment layer that can modestly influence
confidence/ordering based on historical recruiter feedback.

CRITICAL CONSTRAINTS:
  - NEVER overwrites the canonical deterministic fit_score
  - Adjustment is stored SEPARATELY (feedback_adjustment field)
  - Bounded: max +3 / -5 points
  - Per job_id scope (not global)
  - Easy to disable via FEEDBACK_RERANK_ENABLED=false

This module does NOT train any model. It extracts simple structured
signals from the ai_feedbacks table and applies conservative adjustments.
"""
from __future__ import annotations

import logging
import os
from dataclasses import dataclass, field
from typing import Any

logger = logging.getLogger(__name__)

# Maximum adjustment bounds (locked per Phase 15 spec)
_MAX_BOOST: float = 3.0    # max positive adjustment
_MAX_PENALTY: float = -5.0  # max negative adjustment


@dataclass
class FeedbackSignal:
    """Aggregated feedback signal for a job."""
    job_id: int
    agree_count: int = 0
    disagree_count: int = 0
    flag_count: int = 0
    note_count: int = 0
    total: int = 0


@dataclass
class FeedbackAdjustment:
    """Result of feedback-derived adjustment."""
    adjustment_points: float = 0.0  # added to display score (NOT canonical fit_score)
    reason: str = ""
    signal_count: int = 0
    enabled: bool = True

    def to_dict(self) -> dict[str, Any]:
        return {
            "adjustment_points": round(self.adjustment_points, 2),
            "reason": self.reason,
            "signal_count": self.signal_count,
            "enabled": self.enabled,
        }


class FeedbackSignalExtractor:
    """Extracts structured feedback signals from ai_feedbacks table.

    Reads from the same Postgres DB that the ai-service uses.
    When the ai_feedbacks table doesn't exist (e.g., SQLite dev mode),
    returns empty signals gracefully.
    """

    async def extract_for_job(self, job_id: int) -> FeedbackSignal:
        """Get aggregated feedback signals for a specific job."""
        signal = FeedbackSignal(job_id=job_id)

        try:
            from app.services.db import get_pool
            pool = await get_pool()

            # Check if ai_feedbacks table exists (may not in all DB configs)
            table_exists = await pool.fetchval("""
                SELECT EXISTS(
                    SELECT 1 FROM information_schema.tables
                    WHERE table_name = 'ai_feedbacks'
                )
            """)
            if not table_exists:
                logger.debug("ai_feedbacks table not found, skipping feedback extraction")
                return signal

            rows = await pool.fetch("""
                SELECT feedback_type, COUNT(*) as cnt
                FROM ai_feedbacks
                WHERE job_id = $1
                GROUP BY feedback_type
            """, job_id)

            for row in rows:
                ft = row["feedback_type"]
                cnt = int(row["cnt"])
                signal.total += cnt
                if ft == "agree":
                    signal.agree_count = cnt
                elif ft == "disagree":
                    signal.disagree_count = cnt
                elif ft == "flag":
                    signal.flag_count = cnt
                elif ft == "note":
                    signal.note_count = cnt

        except Exception as exc:
            logger.debug("Feedback signal extraction failed: %s", exc)

        return signal


class FeedbackReranker:
    """Applies bounded, explainable adjustments based on recruiter feedback.

    Rules:
      - Only adjusts if sufficient signal exists (≥2 feedback entries)
      - Strong disagree pattern → penalty (max -5 pts)
      - Strong agree pattern → slight boost (max +3 pts)
      - Flag signals → additional caution penalty
      - Adjustment is NEVER applied to canonical fit_score
      - Can be disabled via env var
    """

    def __init__(self) -> None:
        enabled_str = os.getenv("FEEDBACK_RERANK_ENABLED", "true").strip().lower()
        self._enabled = enabled_str in ("true", "1", "yes")
        self._extractor = FeedbackSignalExtractor()

    @property
    def enabled(self) -> bool:
        return self._enabled

    async def adjust(
        self,
        job_id: int,
        fit_score: float,
        confidence_label: str,
    ) -> FeedbackAdjustment:
        """Compute feedback-derived adjustment.

        Args:
            job_id: The job being matched against.
            fit_score: The canonical deterministic fit_score (READ-ONLY).
            confidence_label: Current confidence assessment.

        Returns:
            FeedbackAdjustment with bounded adjustment_points.
            The canonical fit_score is NOT modified.
        """
        if not self._enabled:
            return FeedbackAdjustment(
                adjustment_points=0.0,
                reason="Feedback reranking disabled",
                signal_count=0,
                enabled=False,
            )

        signal = await self._extractor.extract_for_job(job_id)

        # Require minimum signal volume
        if signal.total < 2:
            return FeedbackAdjustment(
                adjustment_points=0.0,
                reason=f"Insufficient feedback signal ({signal.total} entries)",
                signal_count=signal.total,
            )

        adjustment = 0.0
        reasons: list[str] = []

        # Disagree-heavy pattern → penalty
        if signal.disagree_count > 0:
            disagree_ratio = signal.disagree_count / signal.total
            if disagree_ratio >= 0.5:
                # Strong disagree signal
                penalty = -3.0 * disagree_ratio
                adjustment += max(_MAX_PENALTY, penalty)
                reasons.append(
                    f"Disagree ratio {disagree_ratio:.0%} ({signal.disagree_count}/{signal.total})"
                )

        # Agree-heavy pattern → slight boost
        if signal.agree_count > 0:
            agree_ratio = signal.agree_count / signal.total
            if agree_ratio >= 0.6:
                boost = 2.0 * (agree_ratio - 0.5)
                adjustment += min(_MAX_BOOST, boost)
                reasons.append(
                    f"Agree ratio {agree_ratio:.0%} ({signal.agree_count}/{signal.total})"
                )

        # Flag signals → additional caution
        if signal.flag_count >= 2:
            flag_penalty = -1.0 * min(signal.flag_count, 3)
            adjustment += max(-3.0, flag_penalty)
            reasons.append(f"{signal.flag_count} flag(s)")

        # Clamp to bounds
        adjustment = max(_MAX_PENALTY, min(_MAX_BOOST, adjustment))

        reason_text = "; ".join(reasons) if reasons else "No significant pattern"

        return FeedbackAdjustment(
            adjustment_points=round(adjustment, 2),
            reason=reason_text,
            signal_count=signal.total,
        )
