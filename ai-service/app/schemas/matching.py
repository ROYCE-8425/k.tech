from __future__ import annotations

from pydantic import BaseModel, Field


# ---------------------------------------------------------------------------
# Input payloads (from Laravel controller)
# ---------------------------------------------------------------------------


class CandidatePayload(BaseModel):
    id: int
    name: str | None = None
    summary: str | None = None
    about_me: str | None = None
    skills: list[str] | str | None = None
    skills_json: list[str] | str | None = None
    experience: str | None = None
    education: str | None = None
    work_experiences: list[dict] | list[str] | None = None
    profile_data: dict | list | None = None
    # Application.cv_data forwarded from controller (decrypted on backend)
    cv_data: dict | str | None = None


class JobPayload(BaseModel):
    id: int
    title: str
    description: str | None = None
    requirements: str | None = None
    location: str | None = None
    # Phase 1 structured AI matching inputs
    required_skills: list[str] | None = None
    preferred_skills: list[str] | None = None
    seniority: str | None = None           # intern/fresher/junior/mid/senior/lead/principal
    min_experience_years: int | None = None
    max_experience_years: int | None = None
    scoring_config: dict | None = None     # future per-job weight overrides
    ai_recruiter_notes: str | None = None


class MatchOptions(BaseModel):
    include_reasoning: bool = True


class MatchRequest(BaseModel):
    candidate: CandidatePayload
    job: JobPayload
    options: MatchOptions = Field(default_factory=MatchOptions)
    # Phase 3: optional application context for persistence
    application_id: int | None = None


# ---------------------------------------------------------------------------
# Structured intermediate profiles (produced by extractor)
# ---------------------------------------------------------------------------


class CandidateProfile(BaseModel):
    """Structured candidate profile extracted from raw payload."""

    skills: list[str] = Field(default_factory=list)
    experience_years: float | None = None
    seniority: str | None = None          # intern/fresher/junior/mid/senior/lead/principal
    education_level: str | None = None    # bachelor / master / phd / other
    domain_keywords: list[str] = Field(default_factory=list)
    raw_summary: str | None = None
    extraction_method: str = "fallback"   # "llm" | "fallback"
    extraction_confidence: str = "low"    # "high" | "medium" | "low"
    notes: list[str] = Field(default_factory=list)


class JobProfile(BaseModel):
    """Structured job profile extracted from JD text."""

    required_skills: list[str] = Field(default_factory=list)
    preferred_skills: list[str] = Field(default_factory=list)
    seniority: str | None = None
    # Phase 3: experience range for hybrid scoring
    min_experience_years: float | None = None
    max_experience_years: float | None = None
    responsibilities: list[str] = Field(default_factory=list)
    domain_keywords: list[str] = Field(default_factory=list)
    extraction_method: str = "fallback"   # "llm" | "structured" | "fallback"
    extraction_confidence: str = "low"    # "high" | "medium" | "low"
    notes: list[str] = Field(default_factory=list)


# ---------------------------------------------------------------------------
# Output
# ---------------------------------------------------------------------------


class EvidenceItem(BaseModel):
    source: str
    excerpt: str
    score: float
    retrieval_method: str = "unknown"


class ScoreBreakdownItem(BaseModel):
    """Individual component of the hybrid score breakdown."""

    score: float
    weight: float
    weighted: float
    detail: str


class MatchResponse(BaseModel):
    candidate_id: int
    job_id: int
    fit_score: float
    rank_label: str
    matched_skills: list[str]
    missing_skills: list[str]
    missing_preferred_skills: list[str] = Field(default_factory=list)
    score_breakdown: dict[str, ScoreBreakdownItem] = Field(default_factory=dict)
    risk_flags: list[str] = Field(default_factory=list)
    confidence_label: str = "medium"
    reasoning: list[str]
    evidence: list[EvidenceItem]
    retrieval_method: str = "unknown"
    agent_trace: list[str]
    candidate_profile: CandidateProfile | None = None
    job_profile: JobProfile | None = None
    # Phase 15: optional feedback-derived adjustment metadata (not persisted)
    feedback_adjustment: dict | None = None
    pipeline_version: str = "v2.0"
    generated_at: str = ""
