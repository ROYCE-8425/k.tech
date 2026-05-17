from fastapi import APIRouter

from app.schemas.matching import MatchRequest, MatchResponse
from app.services.orchestrator import MatchOrchestrator

router = APIRouter()


@router.post("/match", response_model=MatchResponse)
async def match_candidate_to_job(payload: MatchRequest) -> MatchResponse:
    orchestrator = MatchOrchestrator()
    return await orchestrator.run(payload)


@router.post("/compare")
async def compare_reasoning_modes(payload: MatchRequest) -> dict:
    """AI Decision Lab — compare 4 reasoning modes for one candidate-job pair.

    Reuses the same extraction pipeline as /match, then runs the matcher
    in 4 controlled modes: baseline, graph_1hop, graph_2hop, feedback_aware.

    Returns compact comparison results for recruiter-facing visualization.
    Does NOT persist anything or modify canonical fit_score.
    """
    from app.services.agents import ExtractorAgent, RAGAgent
    from app.services.comparison import ComparisonRunner

    # Step 1: Extract profiles (same as /match — reuses LLM/heuristic)
    extractor = ExtractorAgent()
    c_profile, j_profile = await extractor.run(payload.candidate, payload.job)

    # Step 2: Get retrieval method for context
    rag = RAGAgent()
    evidence, retrieval_method = await rag.run(j_profile, payload.job.model_dump())

    # Step 3: Run comparison across 4 modes
    runner = ComparisonRunner()
    modes = await runner.run_comparison(
        c_profile=c_profile,
        j_profile=j_profile,
        job_title=payload.job.title,
        job_id=payload.job.id,
        scoring_config=payload.job.scoring_config,
    )

    # Step 4: Compute deltas vs baseline
    baseline_score = modes[0]["fit_score"] if modes else 0
    deltas = []
    for i, m in enumerate(modes):
        delta = round(m["fit_score"] - baseline_score, 2)
        explanation = _explain_delta(m, baseline_score) if i > 0 else "Điểm cơ sở — chỉ exact/synonym matching"
        deltas.append({
            "mode": m["mode"],
            "delta": delta,
            "explanation": explanation,
        })

    return {
        "candidate_id": payload.candidate.id,
        "job_id": payload.job.id,
        "extraction_method": c_profile.extraction_method,
        "extraction_confidence": c_profile.extraction_confidence,
        "retrieval_method": retrieval_method,
        "candidate_skills": c_profile.skills[:15],
        "job_required_skills": j_profile.required_skills[:15],
        "modes": modes,
        "deltas": deltas,
        "note": "Canonical persisted fit_score remains unchanged. These are diagnostic comparisons only.",
    }


def _explain_delta(mode_result: dict, baseline_score: float) -> str:
    """Generate a concise delta explanation."""
    delta = round(mode_result["fit_score"] - baseline_score, 2)
    mode = mode_result["mode"]

    if mode == "graph_1hop":
        related = mode_result.get("one_hop_count", 0)
        if delta > 0:
            return f"+{delta} điểm nhờ {related} kỹ năng liên quan trực tiếp (one-hop graph reasoning)"
        elif delta == 0:
            return "Không thay đổi — không có kỹ năng liên quan one-hop áp dụng được"
        else:
            return f"{delta} điểm — confidence bị giảm do tỷ lệ indirect match cao"

    elif mode == "graph_2hop":
        one_hop = mode_result.get("one_hop_count", 0)
        two_hop = mode_result.get("two_hop_count", 0)
        if delta > 0:
            parts = []
            if one_hop:
                parts.append(f"{one_hop} one-hop")
            if two_hop:
                parts.append(f"{two_hop} two-hop")
            return f"+{delta} điểm nhờ {' + '.join(parts)} related skills (full graph reasoning)"
        elif delta == 0:
            return "Không thay đổi — two-hop không tìm thêm kỹ năng liên quan"
        else:
            return f"{delta} điểm — two-hop indirect match giảm confidence"

    elif mode == "feedback_aware":
        fb = mode_result.get("score_breakdown", {}).get("feedback_adjustment", {})
        fb_pts = fb.get("points", 0) if isinstance(fb, dict) else 0
        reason = fb.get("reason", "") if isinstance(fb, dict) else ""
        if fb_pts > 0:
            return f"+{delta} điểm (graph + recruiter feedback boost: {reason})"
        elif fb_pts < 0:
            return f"{delta} điểm (graph + recruiter feedback penalty: {reason})"
        else:
            return f"+{delta} điểm (graph reasoning, chưa đủ feedback signal để điều chỉnh)"

    return f"{'+'if delta>=0 else ''}{delta} điểm"
