# CV Matcher Agent - Implementation

from dataclasses import dataclass
from typing import Any

@dataclass
class ExtractorAgent:
    """Extract structured features from CV and JD"""
    
    def run(self, candidate: dict[str, Any], job: dict[str, Any]) -> dict[str, Any]:
        candidate_skills_raw = candidate.get("skills") or []
        if isinstance(candidate_skills_raw, str):
            candidate_skills = [s.strip().lower() for s in candidate_skills_raw.split(",") if s.strip()]
        else:
            candidate_skills = [str(s).strip().lower() for s in candidate_skills_raw]

        req_text = (job.get("requirements") or "") + " " + (job.get("description") or "")
        required_keywords = [
            "python", "laravel", "php", "postgresql", "docker",
            "rest", "microservices", "ai", "rag",
        ]
        required = [k for k in required_keywords if k in req_text.lower()]

        return {
            "candidate_skills": sorted(set(candidate_skills)),
            "job_required_skills": sorted(set(required)),
            "candidate_summary": candidate.get("summary") or "",
            "job_text": req_text,
        }


@dataclass  
class RAGAgent:
    """Retrieve grounding evidence from knowledge corpus"""
    
    def run(self, extracted: dict[str, Any], job: dict[str, Any]) -> list[dict[str, Any]]:
        corpus = [
            {
                "source": "korean-hiring-guide",
                "excerpt": "Korean employers value clear role-fit evidence and practical project outcomes.",
                "tags": ["culture", "hiring", "fit"],
            },
            {
                "source": "jd-best-practices",
                "excerpt": "Candidates with 60-70% core skills and high learning velocity can still be top performers.",
                "tags": ["skills", "matching"],
            },
        ]

        required = set(extracted.get("job_required_skills", []))
        evidence: list[dict[str, Any]] = []
        for doc in corpus:
            score = 0.2
            if "skills" in doc["tags"] and required:
                score += 0.4
            if "culture" in doc["tags"] and "korean" in (job.get("description") or "").lower():
                score += 0.4
            evidence.append({"source": doc["source"], "excerpt": doc["excerpt"], "score": min(score, 1.0)})
        
        evidence.sort(key=lambda x: x["score"], reverse=True)
        return evidence[:3]


@dataclass
class MatcherAgent:
    """Compute skill overlap and fit scores"""
    
    def run(self, extracted: dict[str, Any]) -> dict[str, Any]:
        candidate_skills = set(extracted.get("candidate_skills", []))
        required = set(extracted.get("job_required_skills", []))
        matched = sorted(candidate_skills.intersection(required))
        missing = sorted(required.difference(candidate_skills))

        if not required:
            fit = 70.0
        else:
            fit = round((len(matched) / max(len(required), 1)) * 100, 2)
            fit = min(98.0, fit + (12.0 if len(matched) >= 3 else 0.0))

        return {
            "fit_score": round(fit, 2),
            "matched_skills": matched,
            "missing_skills": missing,
        }


@dataclass
class ExplainerAgent:
    """Generate citation-aware rationale"""
    
    def run(self, matching: dict[str, Any], evidence: list[dict[str, Any]]) -> list[str]:
        reasons: list[str] = []
        reasons.append(
            f"Matched {len(matching['matched_skills'])} core skills: {', '.join(matching['matched_skills']) or 'none'}."
        )
        if matching["missing_skills"]:
            reasons.append(f"Missing important skills: {', '.join(matching['missing_skills'])}.")
        if evidence:
            reasons.append(f"Grounded by top evidence source: {evidence[0]['source']}.")
        return reasons


@dataclass
class CriticAgent:
    """Validate confidence and adjust edge cases"""
    
    def run(self, fit_score: float, reasoning: list[str]) -> tuple[float, list[str]]:
        notes = []
        adjusted = fit_score
        if fit_score > 90 and len(reasoning) < 2:
            adjusted = 85.0
            notes.append("Score reduced due to weak explanation depth.")
        if fit_score < 40:
            notes.append("Recommend fallback to human review.")
        return adjusted, notes
