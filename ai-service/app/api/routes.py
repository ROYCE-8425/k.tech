from fastapi import APIRouter

from app.schemas.matching import MatchRequest, MatchResponse
from app.services.orchestrator import MatchOrchestrator

router = APIRouter()


@router.post("/match", response_model=MatchResponse)
async def match_candidate_to_job(payload: MatchRequest) -> MatchResponse:
    orchestrator = MatchOrchestrator()
    return await orchestrator.run(payload)
