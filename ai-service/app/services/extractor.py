"""
Structured extraction service for CV / JD data.

Priority order for candidate text:
  1. cv_data  (Application.cv_data forwarded from Laravel — most complete)
  2. about_me + summary + work_experiences
  3. skills_json / skills + experience + education

Job extraction priority:
  1. Structured fields from Laravel payload (required_skills, preferred_skills, seniority, etc.)
  2. LLM extraction from free-text description/requirements
  3. Fallback heuristic extraction

LLM extraction uses env-configured model and JSON mode.
Fallback is deterministic keyword/regex heuristics.
"""
from __future__ import annotations

import json
import logging
import os
import re
from typing import Any

from app.schemas.matching import CandidatePayload, CandidateProfile, JobPayload, JobProfile
from app.services.normalizer import SkillNormalizer, SeniorityNormalizer

_skill_normalizer = SkillNormalizer()
_seniority_normalizer = SeniorityNormalizer()

logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# PII masking — strip before sending to LLM
# ---------------------------------------------------------------------------
_EMAIL_RE = re.compile(r"[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+")
# Targets E.164-style (+country) and local 0-prefix formats (VN/KR common patterns).
# Requires a disambiguating prefix so year ranges (2018-2021), date spans (01/01/2020),
# and bare experience counts ("5 years") are NOT matched.
_PHONE_RE = re.compile(
    r"(?:(?:\+|00)\d{1,3}[-\s.]?|(?<!\d)0(?=\d))"  # +intl / 00intl / local 0-prefix
    r"\d{1,4}(?:[-\s.]?\d{2,5}){2,4}"               # digit groups with separators
)
_URL_RE = re.compile(r"https?://\S+")


def _mask_pii(text: str) -> str:
    text = _EMAIL_RE.sub("[EMAIL]", text)
    text = _PHONE_RE.sub("[PHONE]", text)
    text = _URL_RE.sub("[URL]", text)
    return text


# ---------------------------------------------------------------------------
# Text assembly helpers
# ---------------------------------------------------------------------------

def _candidate_raw_text(payload: CandidatePayload) -> str:
    """Assemble the richest candidate text available, in priority order."""
    parts: list[str] = []

    # 1. cv_data (dict from Application or already-extracted blob)
    if payload.cv_data:
        if isinstance(payload.cv_data, dict):
            # Flatten dict values into readable text
            parts.append(json.dumps(payload.cv_data, ensure_ascii=False))
        elif isinstance(payload.cv_data, str) and payload.cv_data.strip():
            parts.append(payload.cv_data)

    # 2. Narrative fields
    for field in [payload.about_me, payload.summary]:
        if field and field.strip():
            parts.append(field)

    # 3. Work experiences
    if payload.work_experiences:
        if isinstance(payload.work_experiences, list):
            for item in payload.work_experiences:
                if isinstance(item, dict):
                    parts.append(json.dumps(item, ensure_ascii=False))
                elif isinstance(item, str) and item.strip():
                    parts.append(item)

    # 4. Remaining structured fields
    for field in [payload.experience, payload.education]:
        if field and field.strip():
            parts.append(field)

    # 5. skills as fallback text
    skills = payload.skills_json or payload.skills
    if skills:
        if isinstance(skills, list):
            parts.append("Skills: " + ", ".join(str(s) for s in skills))
        elif isinstance(skills, str) and skills.strip():
            parts.append("Skills: " + skills)

    # 6. profile_data — structured candidate data (IT role, primary_role, etc.)
    #    Included only when it is a non-empty dict; list/null values are skipped.
    if payload.profile_data and isinstance(payload.profile_data, dict) and payload.profile_data:
        parts.append("Profile: " + json.dumps(payload.profile_data, ensure_ascii=False))

    return "\n\n".join(parts)


def _job_raw_text(payload: JobPayload) -> str:
    parts = [f"Title: {payload.title}"]
    if payload.description:
        parts.append(payload.description)
    if payload.requirements:
        parts.append(payload.requirements)
    if payload.ai_recruiter_notes:
        parts.append(f"Recruiter notes: {payload.ai_recruiter_notes}")
    return "\n\n".join(parts)


# ---------------------------------------------------------------------------
# Fallback heuristic extractor
# ---------------------------------------------------------------------------

_SKILL_TERMS = [
    "python", "java", "javascript", "typescript", "php", "laravel", "django",
    "fastapi", "flask", "react", "vue", "angular", "nodejs", "node.js",
    "postgresql", "mysql", "mongodb", "redis", "elasticsearch",
    "docker", "kubernetes", "k8s", "aws", "gcp", "azure", "terraform",
    "rest", "graphql", "microservices", "grpc",
    "machine learning", "deep learning", "pytorch", "tensorflow", "scikit-learn",
    "rag", "llm", "openai", "langchain", "embedding",
    "git", "ci/cd", "linux", "bash",
    # Additional skills for broader matching
    "html/css", "tailwind css", "bootstrap", "next.js", "nuxt.js", "svelte",
    "spring boot", ".net", "c#", "go", "ruby", "rails",
    "sql", "sql server", "oracle", "firebase", "dynamodb", "sqlite",
    "jenkins", "nginx", "apache",
    "react native", "flutter", "swift", "kotlin", "ios", "android",
    "rest api", "unit testing", "tdd", "design patterns", "oop",
    "ai/ml", "data analysis", "nlp", "computer vision",
    "excel", "tableau", "powerbi",
]

_SENIORITY_MAP = {
    "intern": "intern",
    "thực tập": "intern",
    "fresher": "fresher",
    "junior": "junior",
    "mid": "mid",
    "mid-level": "mid",
    "middle": "mid",
    "senior": "senior",
    "lead": "lead",
    "tech lead": "lead",
    "staff": "lead",
    "principal": "principal",
    "architect": "principal",
    "manager": "lead",
}

_EDU_MAP = {
    "bachelor": "bachelor",
    "b.s.": "bachelor",
    "b.eng": "bachelor",
    "đại học": "bachelor",
    "master": "master",
    "m.s.": "master",
    "thạc sĩ": "master",
    "phd": "phd",
    "doctorate": "phd",
    "tiến sĩ": "phd",
}


def _extract_skills_heuristic(text: str) -> list[str]:
    lower = text.lower()
    return sorted({s for s in _SKILL_TERMS if re.search(r"\b" + re.escape(s) + r"\b", lower)})


def _extract_seniority(text: str) -> str | None:
    lower = text.lower()
    for kw, level in _SENIORITY_MAP.items():
        if re.search(r"\b" + re.escape(kw) + r"\b", lower):
            return level
    return None


def _extract_education(text: str) -> str | None:
    lower = text.lower()
    for kw, level in _EDU_MAP.items():
        if kw in lower:
            return level
    return None


def _extract_experience_years(text: str) -> float | None:
    # e.g. "3+ years", "2 năm kinh nghiệm", "5 years of experience"
    patterns = [
        r"(\d+(?:\.\d+)?)\s*\+?\s*years?",
        r"(\d+(?:\.\d+)?)\s*năm\s*kinh\s*nghiệm",
    ]
    for pat in patterns:
        m = re.search(pat, text.lower())
        if m:
            return float(m.group(1))
    return None


def _extract_experience_range(text: str) -> tuple[float | None, float | None]:
    """Extract min/max experience years from JD text."""
    lower = text.lower()

    # Range: "3-5 years" or "3 to 5 years"
    m = re.search(r"(\d+(?:\.\d+)?)\s*[-–to]+\s*(\d+(?:\.\d+)?)\s*years?", lower)
    if m:
        return float(m.group(1)), float(m.group(2))

    # "at least N years" / "minimum N years"
    m = re.search(r"(?:at\s+least|minimum|min\.?)\s*(\d+(?:\.\d+)?)\s*\+?\s*years?", lower)
    if m:
        return float(m.group(1)), None

    # "up to N years" / "maximum N years"
    m = re.search(r"(?:up\s+to|maximum|max\.?)\s*(\d+(?:\.\d+)?)\s*years?", lower)
    if m:
        return None, float(m.group(1))

    # "N+ years"
    m = re.search(r"(\d+(?:\.\d+)?)\s*\+\s*years?", lower)
    if m:
        return float(m.group(1)), None

    # Plain "N years of experience" / "N years experience"
    m = re.search(r"(\d+(?:\.\d+)?)\s*years?\s*(?:of\s+)?experience", lower)
    if m:
        return float(m.group(1)), None

    return None, None


def _find_preferred_section(lower: str) -> int | None:
    """Return the earliest char index where a preferred-section header appears."""
    markers = ("preferred", "nice to have", "bonus")
    positions = [lower.find(m) for m in markers]
    valid = [p for p in positions if p >= 0]
    return min(valid) if valid else None


class FallbackExtractor:
    """Deterministic heuristic extraction — always works, low precision."""

    def extract_candidate(self, payload: CandidatePayload) -> CandidateProfile:
        text = _candidate_raw_text(payload)
        # Also merge explicit skills list
        explicit_skills: list[str] = []
        skills_src = payload.skills_json or payload.skills
        if skills_src:
            if isinstance(skills_src, list):
                explicit_skills = [str(s).strip() for s in skills_src if s]
            elif isinstance(skills_src, str):
                explicit_skills = [s.strip() for s in skills_src.split(",") if s.strip()]

        heuristic_skills = _extract_skills_heuristic(text)
        all_skills = _skill_normalizer.normalize_skills(explicit_skills + heuristic_skills)

        # Normalize seniority
        raw_seniority = _extract_seniority(text)
        seniority = _seniority_normalizer.normalize(raw_seniority) or raw_seniority

        return CandidateProfile(
            skills=all_skills,
            experience_years=_extract_experience_years(text),
            seniority=seniority,
            education_level=_extract_education(text),
            domain_keywords=_extract_skills_heuristic(text),
            raw_summary=(payload.summary or payload.about_me or "")[:500],
            extraction_method="fallback",
            extraction_confidence="low" if not all_skills else "medium",
            notes=["Extracted using keyword heuristics. LLM unavailable."],
        )

    def extract_job(self, payload: JobPayload) -> JobProfile:
        text = _job_raw_text(payload)
        lower = text.lower()
        all_skills = _extract_skills_heuristic(text)

        # Split required vs preferred using the earliest preferred-section marker.
        preferred: list[str] = []
        split_idx = _find_preferred_section(lower)
        if split_idx is not None:
            pref_text = lower[split_idx:]
            preferred = _extract_skills_heuristic(pref_text)
            required = [s for s in all_skills if s not in preferred]
        else:
            required = all_skills

        # Responsibilities: lines starting with "-", "•", or verb patterns
        resp_lines = [
            line.strip(" -•*") for line in text.splitlines()
            if line.strip() and (
                line.strip().startswith(("-", "•", "*")) or
                re.match(r"^[A-Z][a-z]+\s", line.strip())
            )
        ]

        min_exp, max_exp = _extract_experience_range(text)

        return JobProfile(
            required_skills=required,
            preferred_skills=preferred,
            seniority=_extract_seniority(text),
            min_experience_years=min_exp,
            max_experience_years=max_exp,
            responsibilities=resp_lines[:10],
            domain_keywords=required[:15],
            extraction_method="fallback",
            extraction_confidence="medium",
            notes=[],
        )


# ---------------------------------------------------------------------------
# Structured job extraction — prefers DB fields over free-text parsing
# ---------------------------------------------------------------------------

class StructuredJobExtractor:
    """Extract JobProfile from structured payload fields first.

    When the Laravel payload contains Phase 1 structured fields
    (required_skills, preferred_skills, seniority, experience range),
    use them directly instead of parsing free-text. Only fill gaps
    from heuristic/LLM extraction.
    """

    def extract(self, payload: JobPayload) -> JobProfile | None:
        """Returns a JobProfile if structured fields are sufficient, else None."""
        has_structured = bool(
            payload.required_skills
            or payload.preferred_skills
            or payload.seniority
            or payload.min_experience_years is not None
            or payload.max_experience_years is not None
        )

        if not has_structured:
            return None

        # Use structured fields directly, normalized
        required = _skill_normalizer.normalize_skills(list(payload.required_skills or []))
        preferred = _skill_normalizer.normalize_skills(list(payload.preferred_skills or []))
        seniority = _seniority_normalizer.normalize(payload.seniority) or payload.seniority
        min_exp = float(payload.min_experience_years) if payload.min_experience_years is not None else None
        max_exp = float(payload.max_experience_years) if payload.max_experience_years is not None else None

        # Supplement domain_keywords from normalized skills
        domain_keywords = sorted(set(
            s.lower() for s in (required + preferred)
        ))

        # Extract responsibilities from free-text if available (non-scoring enrichment)
        text = _job_raw_text(payload)
        resp_lines = [
            line.strip(" -•*") for line in text.splitlines()
            if line.strip() and line.strip().startswith(("-", "•", "*"))
        ]

        # Determine confidence based on field coverage
        field_count = sum([
            bool(required),
            bool(preferred),
            bool(seniority),
            min_exp is not None,
        ])
        confidence = "high" if field_count >= 3 else "medium"

        notes = ["Job profile constructed from structured DB fields."]
        if payload.ai_recruiter_notes:
            notes.append(f"Recruiter notes: {payload.ai_recruiter_notes}")

        return JobProfile(
            required_skills=required,
            preferred_skills=preferred,
            seniority=seniority,
            min_experience_years=min_exp,
            max_experience_years=max_exp,
            responsibilities=resp_lines[:10],
            domain_keywords=domain_keywords[:15],
            extraction_method="structured",
            extraction_confidence=confidence,
            notes=notes,
        )


# ---------------------------------------------------------------------------
# LLM extractor
# ---------------------------------------------------------------------------

_CANDIDATE_EXTRACT_PROMPT = """\
You are a senior recruiter. Extract structured information from the following candidate text.
Respond with ONLY valid JSON matching this schema:
{
  "skills": ["string"],
  "experience_years": number | null,
  "seniority": "intern" | "fresher" | "junior" | "mid" | "senior" | "lead" | "principal" | null,
  "education_level": "bachelor" | "master" | "phd" | "other" | null,
  "domain_keywords": ["string"],
  "raw_summary": "string (max 300 chars)",
  "extraction_confidence": "high" | "medium" | "low",
  "notes": ["string"]
}
Rules:
- skills: only concrete, verifiable technical skills (no soft skills, no company names)
- Do not invent fields absent from the text; use null or empty array instead
- raw_summary: one concise sentence describing the candidate
"""

_JOB_EXTRACT_PROMPT = """\
You are a senior recruiter. Extract structured information from this job description.
Respond with ONLY valid JSON matching this schema:
{
  "required_skills": ["string"],
  "preferred_skills": ["string"],
  "seniority": "intern" | "fresher" | "junior" | "mid" | "senior" | "lead" | "principal" | null,
  "min_experience_years": number | null,
  "max_experience_years": number | null,
  "responsibilities": ["string"],
  "domain_keywords": ["string"],
  "extraction_confidence": "high" | "medium" | "low",
  "notes": ["string"]
}
Rules:
- required_skills: only skills listed as mandatory / must-have
- preferred_skills: only skills listed as nice-to-have / preferred / bonus
- min_experience_years: minimum years of experience required (null if not mentioned)
- max_experience_years: maximum/upper-bound years of experience (null if not mentioned)
- responsibilities: up to 8 bullet-point style phrases
- Do not invent fields; prefer empty arrays over guesses
"""


class LLMExtractor:
    """Extraction via provider-abstracted LLM in JSON mode.

    Supports OpenAI, Gemini, xAI/Grok via the provider abstraction layer.
    Provider selection is handled by `create_provider()` in llm_providers.py.
    """

    def __init__(self, provider) -> None:
        from app.services.llm_providers import LLMProvider
        self._provider: LLMProvider = provider

    @property
    def provider_name(self) -> str:
        return self._provider.provider_name

    @property
    def model_name(self) -> str:
        return self._provider.model_name

    async def _call(self, system_prompt: str, user_text: str) -> dict[str, Any]:
        return await self._provider.complete_json(
            system_prompt=system_prompt,
            user_text=user_text[:6000],  # safety truncation
            max_tokens=1024,
        )

    async def extract_candidate(self, payload: CandidatePayload) -> CandidateProfile:
        text = _mask_pii(_candidate_raw_text(payload))
        if not text.strip():
            raise ValueError("No candidate text to extract from")

        data = await self._call(_CANDIDATE_EXTRACT_PROMPT, text)

        raw_skills = data.get("skills") or []
        raw_seniority = data.get("seniority")

        return CandidateProfile(
            skills=_skill_normalizer.normalize_skills(raw_skills),
            experience_years=data.get("experience_years"),
            seniority=_seniority_normalizer.normalize(raw_seniority) or raw_seniority,
            education_level=data.get("education_level"),
            domain_keywords=data.get("domain_keywords") or [],
            raw_summary=data.get("raw_summary") or "",
            extraction_method="llm",
            extraction_confidence=data.get("extraction_confidence", "medium"),
            notes=data.get("notes") or [],
        )

    async def extract_job(self, payload: JobPayload) -> JobProfile:
        text = _job_raw_text(payload)
        if not text.strip():
            raise ValueError("No job text to extract from")

        data = await self._call(_JOB_EXTRACT_PROMPT, text)

        raw_req = data.get("required_skills") or []
        raw_pref = data.get("preferred_skills") or []
        raw_seniority = data.get("seniority")

        return JobProfile(
            required_skills=_skill_normalizer.normalize_skills(raw_req),
            preferred_skills=_skill_normalizer.normalize_skills(raw_pref),
            seniority=_seniority_normalizer.normalize(raw_seniority) or raw_seniority,
            min_experience_years=data.get("min_experience_years"),
            max_experience_years=data.get("max_experience_years"),
            responsibilities=data.get("responsibilities") or [],
            domain_keywords=data.get("domain_keywords") or [],
            extraction_method="llm",
            extraction_confidence=data.get("extraction_confidence", "medium"),
            notes=data.get("notes") or [],
        )


# ---------------------------------------------------------------------------
# Public facade — structured-first for jobs, LLM-first for candidates
# ---------------------------------------------------------------------------

class ExtractionService:
    def __init__(self) -> None:
        from app.services.llm_providers import create_provider

        self._fallback = FallbackExtractor()
        self._structured_job = StructuredJobExtractor()

        provider = create_provider()
        self._llm: LLMExtractor | None = LLMExtractor(provider) if provider else None

        # Expose provider metadata for trace/logging
        self.provider_name: str = provider.provider_name if provider else "none"
        self.model_name: str = provider.model_name if provider else "heuristic"

    async def extract_candidate(self, payload: CandidatePayload) -> CandidateProfile:
        if self._llm:
            try:
                return await self._llm.extract_candidate(payload)
            except Exception as exc:
                logger.warning(
                    "LLM candidate extraction failed (provider=%s, error=%s), using fallback",
                    self.provider_name, exc,
                )
        return self._fallback.extract_candidate(payload)

    async def extract_job(self, payload: JobPayload) -> JobProfile:
        """Structured-first job extraction.

        Priority:
          1. Structured DB fields (required_skills, seniority, etc.)
          2. LLM extraction from free-text
          3. Fallback heuristic extraction
        """
        # 1. Try structured extraction from Phase 1 DB fields
        structured = self._structured_job.extract(payload)
        if structured is not None:
            logger.info(
                "Job %s: using structured extraction (confidence=%s)",
                payload.id, structured.extraction_confidence,
            )
            return structured

        # 2. Try LLM extraction
        if self._llm:
            try:
                return await self._llm.extract_job(payload)
            except Exception as exc:
                logger.warning(
                    "LLM job extraction failed (provider=%s, error=%s), using fallback",
                    self.provider_name, exc,
                )

        # 3. Fallback heuristic
        return self._fallback.extract_job(payload)
