#!/usr/bin/env python3
"""
Evaluation runner for Smart CV Matcher AI pipeline.

Supports 4 ablation modes per Phase 15 spec:
  - exact_only:    exact/synonym matching baseline (no graph)
  - one_hop:       + one-hop graph reasoning
  - two_hop:       + two-hop graph reasoning (default)
  - feedback_on:   + feedback reranking enabled
  - feedback_off:  two-hop, feedback disabled

Usage:
    python evals/runner.py                     # default (two_hop)
    python evals/runner.py --mode exact_only   # baseline
    python evals/runner.py --compare           # run all modes, compare
"""
from __future__ import annotations

import argparse
import asyncio
import json
import logging
import os
import sys
import time
from pathlib import Path
from typing import Any

# Add project root to path for imports
_PROJECT_ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(_PROJECT_ROOT))

# Load .env file if present
_ENV_FILE = _PROJECT_ROOT / ".env"
if _ENV_FILE.exists():
    for line in _ENV_FILE.read_text().splitlines():
        line = line.strip()
        if line and not line.startswith("#") and "=" in line:
            key, _, value = line.partition("=")
            os.environ.setdefault(key.strip(), value.strip())

logging.basicConfig(level=logging.WARNING, format="%(levelname)s: %(message)s")
logger = logging.getLogger("eval")

# Ablation modes
MODES = ["exact_only", "one_hop", "two_hop", "feedback_off", "feedback_on"]

# Score band classification
def _score_band(score: float) -> str:
    if score >= 80:
        return "high"
    elif score >= 60:
        return "medium"
    return "low"


def _load_dataset(path: str | None = None) -> list[dict]:
    """Load evaluation dataset."""
    if path is None:
        path = str(Path(__file__).parent / "dataset_v1.json")
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)
    return data.get("cases", [])


async def _run_single_case(
    case: dict,
    mode: str,
) -> dict[str, Any]:
    """Run a single evaluation case through the matching pipeline."""
    from app.schemas.matching import CandidatePayload, JobPayload, MatchRequest
    from app.services.agents import MatcherAgent, ExplainerAgent, CriticAgent, RAGAgent, ExtractorAgent
    from app.services.feedback_reranker import FeedbackReranker

    # Prepare payloads
    candidate = CandidatePayload(**case["candidate"])
    job = JobPayload(**case["job"])

    # Run extraction
    extractor = ExtractorAgent()
    c_profile, j_profile = await extractor.run(candidate, job)

    # Run RAG
    rag = RAGAgent()
    evidence, retrieval_method = await rag.run(j_profile, job.model_dump())

    # Configure matcher based on mode
    matcher = MatcherAgent()

    # For exact_only: disable graph reasoning in skill matching
    if mode == "exact_only":
        # Run matcher but skip related matches by passing empty graph results
        from app.services.normalizer import SkillNormalizer
        norm = SkillNormalizer()
        matched_req, missing_req = norm.match_skills(c_profile.skills, j_profile.required_skills)
        matched_pref, missing_pref = norm.match_skills(c_profile.skills, j_profile.preferred_skills)

        # Build minimal matching result (exact/synonym only)
        matching = matcher.run(c_profile, j_profile, job_title=job.title)
        # Zero out related matches for scoring
        matching["related_matches"] = []
    elif mode == "one_hop":
        # Temporarily disable two-hop in the graph
        matching = matcher.run(c_profile, j_profile, job_title=job.title)
        # Re-run with one-hop only by filtering out two-hop matches
        matching["related_matches"] = [
            r for r in matching.get("related_matches", [])
            if r.get("hop_count", 1) == 1
        ]
    else:
        # two_hop, feedback_on, feedback_off — full matching
        matching = matcher.run(c_profile, j_profile, job_title=job.title)

    # Explanation
    explainer = ExplainerAgent()
    reasons = explainer.run(matching, evidence, c_profile, j_profile)

    # Critic
    critic = CriticAgent()
    final_score, critic_notes = critic.run(matching["fit_score"], reasons)

    # Feedback reranking
    feedback_adj = None
    if mode == "feedback_on":
        os.environ["FEEDBACK_RERANK_ENABLED"] = "true"
        reranker = FeedbackReranker()
        feedback_adj = await reranker.adjust(job.id, final_score, matching.get("confidence_label", "medium"))
    elif mode == "feedback_off":
        os.environ["FEEDBACK_RERANK_ENABLED"] = "false"

    # Build result
    actual_band = _score_band(final_score)
    expected = case.get("expected", {})
    expected_band = expected.get("score_band", "")
    expected_rank = expected.get("rank_label", "")

    rank = "high_fit" if final_score >= 80 else "medium_fit" if final_score >= 60 else "low_fit"

    return {
        "case_id": case["id"],
        "description": case.get("description", ""),
        "mode": mode,
        "fit_score": round(final_score, 2),
        "actual_band": actual_band,
        "expected_band": expected_band,
        "band_correct": actual_band == expected_band,
        "rank_label": rank,
        "expected_rank": expected_rank,
        "rank_correct": rank == expected_rank,
        "matched_skills": matching.get("matched_skills", []),
        "missing_skills": matching.get("missing_skills", []),
        "related_matches": len(matching.get("related_matches", [])),
        "one_hop_matches": sum(1 for r in matching.get("related_matches", []) if r.get("hop_count", 1) == 1),
        "two_hop_matches": sum(1 for r in matching.get("related_matches", []) if r.get("hop_count", 1) == 2),
        "evidence_count": len(evidence),
        "retrieval_method": retrieval_method,
        "extraction_method_candidate": c_profile.extraction_method,
        "extraction_method_job": j_profile.extraction_method,
        "confidence_label": matching.get("confidence_label", ""),
        "feedback_adjustment": feedback_adj.to_dict() if feedback_adj else None,
        "must_match_check": _check_must_match(
            matching.get("matched_skills", []),
            expected.get("must_match_skills", []),
        ),
        "must_miss_check": _check_must_miss(
            matching.get("missing_skills", []),
            expected.get("must_miss_skills", []),
        ),
    }


def _check_must_match(matched: list[str], must_match: list[str]) -> dict:
    """Check if expected must-match skills were actually matched."""
    if not must_match:
        return {"total": 0, "found": 0, "missing": [], "precision": 1.0}

    matched_lower = {s.lower() for s in matched}
    found = [s for s in must_match if s.lower() in matched_lower]
    missing = [s for s in must_match if s.lower() not in matched_lower]
    return {
        "total": len(must_match),
        "found": len(found),
        "missing": missing,
        "precision": len(found) / len(must_match) if must_match else 1.0,
    }


def _check_must_miss(missing_skills: list[str], must_miss: list[str]) -> dict:
    """Check if expected must-miss skills were correctly identified."""
    if not must_miss:
        return {"total": 0, "found": 0, "not_detected": [], "recall": 1.0}

    missing_lower = {s.lower() for s in missing_skills}
    found = [s for s in must_miss if s.lower() in missing_lower]
    not_detected = [s for s in must_miss if s.lower() not in missing_lower]
    return {
        "total": len(must_miss),
        "found": len(found),
        "not_detected": not_detected,
        "recall": len(found) / len(must_miss) if must_miss else 1.0,
    }


def _compute_metrics(results: list[dict]) -> dict:
    """Compute aggregate metrics from evaluation results."""
    n = len(results)
    if n == 0:
        return {}

    band_correct = sum(1 for r in results if r["band_correct"])
    rank_correct = sum(1 for r in results if r["rank_correct"])

    must_match_precision = [
        r["must_match_check"]["precision"]
        for r in results if r["must_match_check"]["total"] > 0
    ]
    must_miss_recall = [
        r["must_miss_check"]["recall"]
        for r in results if r["must_miss_check"]["total"] > 0
    ]

    evidence_present = sum(1 for r in results if r["evidence_count"] > 0)

    retrieval_dist = {}
    for r in results:
        m = r["retrieval_method"]
        retrieval_dist[m] = retrieval_dist.get(m, 0) + 1

    extraction_dist = {}
    for r in results:
        m = r["extraction_method_candidate"]
        extraction_dist[m] = extraction_dist.get(m, 0) + 1

    return {
        "total_cases": n,
        "score_band_accuracy": round(band_correct / n, 4),
        "rank_label_agreement": round(rank_correct / n, 4),
        "must_match_precision": round(
            sum(must_match_precision) / len(must_match_precision), 4
        ) if must_match_precision else None,
        "must_miss_recall": round(
            sum(must_miss_recall) / len(must_miss_recall), 4
        ) if must_miss_recall else None,
        "evidence_presence": round(evidence_present / n, 4),
        "avg_fit_score": round(sum(r["fit_score"] for r in results) / n, 2),
        "retrieval_method_distribution": retrieval_dist,
        "extraction_method_distribution": extraction_dist,
    }


async def run_eval(mode: str, dataset_path: str | None = None) -> dict:
    """Run full evaluation in a specific mode."""
    cases = _load_dataset(dataset_path)
    results = []

    for case in cases:
        try:
            result = await _run_single_case(case, mode)
            results.append(result)
        except Exception as exc:
            logger.error("Case %s failed: %s", case.get("id", "?"), exc)
            results.append({
                "case_id": case.get("id", "?"),
                "mode": mode,
                "error": str(exc),
                "band_correct": False,
                "rank_correct": False,
                "must_match_check": {"total": 0, "found": 0, "missing": [], "precision": 1.0},
                "must_miss_check": {"total": 0, "found": 0, "not_detected": [], "recall": 1.0},
                "evidence_count": 0,
                "retrieval_method": "error",
                "extraction_method_candidate": "error",
                "fit_score": 0,
            })

    metrics = _compute_metrics(results)

    return {
        "mode": mode,
        "metrics": metrics,
        "results": results,
        "timestamp": time.strftime("%Y-%m-%dT%H:%M:%S%z"),
    }


async def run_compare(dataset_path: str | None = None) -> dict:
    """Run all ablation modes and produce comparison."""
    comparison = {}
    for mode in MODES:
        logger.warning("Running mode: %s", mode)
        result = await run_eval(mode, dataset_path)
        comparison[mode] = result["metrics"]

    return comparison


def _print_results(eval_result: dict) -> None:
    """Pretty-print evaluation results."""
    mode = eval_result["mode"]
    metrics = eval_result["metrics"]

    print(f"\n{'='*60}")
    print(f"  Evaluation Results — Mode: {mode}")
    print(f"{'='*60}")
    print(f"  Total cases:          {metrics.get('total_cases', 0)}")
    print(f"  Score band accuracy:  {metrics.get('score_band_accuracy', 0):.1%}")
    print(f"  Rank label agreement: {metrics.get('rank_label_agreement', 0):.1%}")
    print(f"  Must-match precision: {metrics.get('must_match_precision', 'N/A')}")
    print(f"  Must-miss recall:     {metrics.get('must_miss_recall', 'N/A')}")
    print(f"  Evidence presence:    {metrics.get('evidence_presence', 0):.1%}")
    print(f"  Avg fit score:        {metrics.get('avg_fit_score', 0)}")
    print(f"  Retrieval methods:    {metrics.get('retrieval_method_distribution', {})}")
    print(f"  Extraction methods:   {metrics.get('extraction_method_distribution', {})}")
    print()

    for r in eval_result.get("results", []):
        status = "✅" if r.get("band_correct") else "❌"
        score = r.get("fit_score", 0)
        band = r.get("actual_band", "?")
        expected = r.get("expected_band", "?")
        desc = r.get("description", r.get("case_id", ""))
        related = r.get("related_matches", 0)
        one_hop = r.get("one_hop_matches", 0)
        two_hop = r.get("two_hop_matches", 0)
        print(f"  {status} {r.get('case_id','')}: score={score}, band={band} (expected={expected}), "
              f"related={related} (1h={one_hop},2h={two_hop}) — {desc}")


def _print_comparison(comparison: dict) -> None:
    """Pretty-print comparison table."""
    print(f"\n{'='*80}")
    print(f"  Ablation Comparison")
    print(f"{'='*80}")
    print(f"  {'Mode':<16} {'Band Acc':>10} {'Rank Agr':>10} {'Match P':>10} {'Miss R':>10} {'Avg Score':>10}")
    print(f"  {'-'*66}")

    for mode in MODES:
        m = comparison.get(mode, {})
        ba = f"{m.get('score_band_accuracy', 0):.1%}"
        ra = f"{m.get('rank_label_agreement', 0):.1%}"
        mp = f"{m.get('must_match_precision', 'N/A')}"
        if isinstance(mp, float):
            mp = f"{mp:.2f}"
        mr = f"{m.get('must_miss_recall', 'N/A')}"
        if isinstance(mr, float):
            mr = f"{mr:.2f}"
        avg = f"{m.get('avg_fit_score', 0):.1f}"
        print(f"  {mode:<16} {ba:>10} {ra:>10} {mp:>10} {mr:>10} {avg:>10}")

    print()


def main():
    parser = argparse.ArgumentParser(description="AI Match Evaluation Runner")
    parser.add_argument(
        "--mode",
        choices=MODES,
        default="two_hop",
        help="Ablation mode (default: two_hop)",
    )
    parser.add_argument(
        "--compare",
        action="store_true",
        help="Run all modes and compare",
    )
    parser.add_argument(
        "--dataset",
        default=None,
        help="Path to dataset JSON file",
    )
    parser.add_argument(
        "--output",
        default=None,
        help="Path to save JSON results",
    )
    args = parser.parse_args()

    if args.compare:
        comparison = asyncio.run(run_compare(args.dataset))
        _print_comparison(comparison)
        if args.output:
            with open(args.output, "w") as f:
                json.dump(comparison, f, indent=2, default=str)
            print(f"  Results saved to {args.output}")
    else:
        result = asyncio.run(run_eval(args.mode, args.dataset))
        _print_results(result)
        if args.output:
            with open(args.output, "w") as f:
                json.dump(result, f, indent=2, default=str)
            print(f"  Results saved to {args.output}")


if __name__ == "__main__":
    main()
