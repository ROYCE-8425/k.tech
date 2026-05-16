# AI Match Contracts — Design Lock

> **Status**: Locked for implementation. All contracts are canonical.
> **Version**: `v1.1`
> **Last updated**: `2026-05-16`
> **Governing spec**: `skills/OPENSPEC.md`

---

## 1. Match Request Contract

**Direction**: Laravel → FastAPI `POST /api/v1/match`

```jsonc
{
  // ── Candidate context ──────────────────────────────────────
  "candidate": {
    "id": 42,                            // int, required
    "name": "Nguyễn Văn A",              // string | null
    "summary": "Backend dev 3 năm...",   // string | null
    "about_me": "...",                   // string | null
    "skills": ["PHP", "Laravel"],        // list[str] | str | null
    "skills_json": ["PHP", "Laravel"],   // list[str] | str | null (preferred canonical)
    "experience": "Mid (3 năm)",         // string | null (free text)
    "education": "ĐH Bách Khoa...",     // string | null (free text)
    "work_experiences": [                // list[dict] | null
      {
        "company_name": "TechStartup VN",
        "position_title": "Backend Dev",
        "start_date": "2022-06",
        "end_date": null,
        "is_current": true,
        "description": "..."
      }
    ],
    "profile_data": {},                  // dict | null (legacy catch-all)
    "cv_data": {}                        // dict | str | null (richest source from Application)
  },

  // ── Job context ────────────────────────────────────────────
  "job": {
    "id": 7,                             // int, required
    "title": "Backend Developer",        // string, required
    "description": "...",                // string | null
    "requirements": "...",               // string | null (legacy free-text)
    "location": "TP.HCM",               // string | null
    // Phase 1 structured fields ↓
    "required_skills": ["Node.js", "PostgreSQL"],   // list[str] | null
    "preferred_skills": ["Kubernetes"],              // list[str] | null
    "seniority": "mid",                              // enum str | null
    "min_experience_years": 2,                       // int | null
    "max_experience_years": 5,                       // int | null
    "scoring_config": null,                          // dict | null (future override)
    "ai_recruiter_notes": "Ưu tiên microservices"   // string | null
  },

  // ── Options ────────────────────────────────────────────────
  "options": {
    "include_reasoning": true            // bool — include LLM-generated reasoning list
  },

  // ── Application context ────────────────────────────────────
  "application_id": 123                  // int | null — required for persistence
}
```

### Field priority rules
- `candidate.cv_data` > `candidate.profile_data` > `candidate.skills_json` > `candidate.skills`
- `job.required_skills` (structured) > skills parsed from `job.requirements` (free-text)
- `job.seniority` (structured) > seniority inferred from `job.description`

---

## 2. Full AI Response Contract

**Direction**: FastAPI → Laravel

```jsonc
{
  "candidate_id": 42,                    // int
  "job_id": 7,                           // int
  "fit_score": 78.5,                     // float 0-100
  "rank_label": "medium_fit",            // enum: see §6
  "confidence_label": "high",            // enum: see §6

  "matched_skills": ["Node.js", "Docker", "Git"],
  "missing_skills": ["Java"],                       // required skills truly not met (after related matching)
  "missing_preferred_skills": ["Kubernetes"],        // preferred skills truly not met

  "related_matches": [                               // list — related-skill partial matches (v1.1)
    {
      "candidate_skill": "PHP",                      // canonical skill the candidate has
      "target_skill": "Laravel",                     // canonical skill the job requires
      "relation_type": "prerequisite",               // see §6 relation_type enum
      "similarity": 0.55                             // float 0.0-0.85
    }
  ],

  "score_breakdown": {                   // dict[str, ScoreBreakdownItem]
    "required_skill_coverage": { "score": 0.83, "weight": 0.40, "weighted": 33.2, "detail": "3 exact + 1 related / 5 bắt buộc" },
    "preferred_skill_coverage": { "score": 0.50, "weight": 0.15, "weighted": 7.5, "detail": "2 exact / 4 ưu tiên" },
    "experience_fit":           { "score": 0.90, "weight": 0.15, "weighted": 13.5, "detail": "3yr / 2-5yr" },
    "seniority_fit":            { "score": 0.85, "weight": 0.10, "weighted": 8.5,  "detail": "mid→mid" },
    "domain_relevance":         { "score": 0.80, "weight": 0.10, "weighted": 8.0,  "detail": "backend" },
    "confidence_adjustment":    { "score": 0.75, "weight": 0.10, "weighted": 7.5,  "detail": "high/medium" }
  },

  "risk_flags": [                        // list[str] — human-readable warnings
    "Có 1 kỹ năng bắt buộc chỉ phù hợp gián tiếp (related skill)"
  ],

  "reasoning": [                         // list[str] — LLM-generated rationale (NOT persisted)
    "Matched 3 skills (exact/synonym): Node.js, Docker, Git.",
    "Found 1 related-skill match (partial credit):",
    "  • PHP → Laravel (prerequisite for, 55% similarity)"
  ],

  "evidence": [                          // list[EvidenceItem] (NOT persisted)
    { "source": "IT Skill Taxonomy", "excerpt": "Node.js is a...", "score": 0.92, "retrieval_method": "pgvector" }
  ],

  "retrieval_method": "pgvector",        // "pgvector" | "static_fallback" | "none"
  "agent_trace": [                       // list[str] — pipeline debug log (NOT persisted)
    "ExtractorAgent: produced CandidateProfile (llm, conf=high)...",
    "MatcherAgent: fit_score=78.5, matched=3, related=1, missing=1, confidence=high"
  ],

  "candidate_profile": { ... },          // CandidateProfile | null (NOT persisted)
  "job_profile": { ... },                // JobProfile | null (NOT persisted)

  "pipeline_version": "v1.1",           // string
  "generated_at": "2026-05-16T12:00:00Z" // ISO 8601
}
```

---

## 3. Sanitized Persistence Contract

**Stored in**: `applications.ai_match_result` (JSONB)
**Builder**: `AdminController::buildSanitizedAuditRecord()`

```jsonc
{
  "fit_score": 78.5,
  "rank_label": "medium_fit",
  "confidence_label": "high",
  "matched_skills": ["Node.js", "Docker", "Git"],
  "missing_skills": ["Java"],
  "missing_preferred_skills": ["Kubernetes"],
  "related_matches": [                              // v1.1 — compact shape
    { "candidate_skill": "PHP", "target_skill": "Laravel", "relation_type": "prerequisite", "similarity": 0.55 }
  ],
  "risk_flags": ["Có 1 kỹ năng bắt buộc chỉ phù hợp gián tiếp (related skill)"],
  "score_breakdown": {
    "required_skill_coverage": { "score": 0.83, "weight": 0.40, "weighted": 33.2, "detail": "3 exact + 1 related / 5 bắt buộc" },
    "preferred_skill_coverage": { "score": 0.50, "weight": 0.15, "weighted": 7.5,  "detail": "2 exact / 4 ưu tiên" },
    "experience_fit":           { "score": 0.90, "weight": 0.15, "weighted": 13.5, "detail": "3yr / 2-5yr" },
    "seniority_fit":            { "score": 0.85, "weight": 0.10, "weighted": 8.5,  "detail": "mid→mid" },
    "domain_relevance":         { "score": 0.80, "weight": 0.10, "weighted": 8.0,  "detail": "backend" },
    "confidence_adjustment":    { "score": 0.75, "weight": 0.10, "weighted": 7.5,  "detail": "high/medium" }
  },
  "retrieval_method": "pgvector",
  "pipeline_version": "v1.1",
  "generated_at": "2026-05-16T12:00:00Z"
}
```

**Persisted fields**: 13 total (12 from v1.0 + `related_matches` in v1.1).

**Explicitly NOT persisted**: `reasoning`, `evidence`, `agent_trace`, `candidate_profile`, `job_profile`, any raw candidate text.

---

## 4. Recruiter Shortlist View Model

The shortlist UI (`admin/ai-shortlist.blade.php`) consumes these fields per application:

| Field | Source | Type | Display |
|-------|--------|------|---------|
| `candidate_name` | `candidate.name` | string | Name + initials avatar |
| `fit_score` | persisted | float 0-100 | Large colored badge |
| `rank_label` | persisted | enum | Colored pill (see §6) |
| `confidence_label` | persisted | enum | Colored pill (see §6) |
| `matched_skills` | persisted | list[str] | Green skill chips |
| `missing_skills` | persisted | list[str] | Red skill chips |
| `missing_preferred_skills` | persisted | list[str] | Amber skill chips |
| `related_matches` | persisted | list[obj] | Blue related-skill chips with provenance |
| `risk_flags` | persisted | list[str] | Orange warning blocks |
| `score_breakdown` | persisted | dict | Progress bars per component |
| `retrieval_method` | persisted | string | Metadata label |
| `pipeline_version` | persisted | string | Metadata label |
| `generated_at` | persisted | ISO 8601 | Date display + freshness indicator |
| `fresh` | computed | bool | "Mới tính" badge |
| `stale` | computed | bool | "Cũ" badge (>7 days) |

### Score display rules
- `fit_score ≥ 80` → emerald gradient
- `fit_score 60-79` → amber gradient
- `fit_score < 60` → red gradient
- `fit_score null` → gray gradient, display `--`

---

## 5. Candidate Advisory View Model

Derived from the same AI result but presented in softer, action-oriented language.

| UI Label (Vietnamese) | Source Field | Presentation |
|----------------------|--------------|--------------|
| Mức phù hợp | `rank_label` | "Phù hợp cao" / "Phù hợp vừa" / "Cần cải thiện" |
| Kỹ năng phù hợp | `matched_skills` | Green chip list |
| Nền tảng liên quan | `related_matches` | Soft phrasing: "Bạn đã có nền tảng gần với X thông qua Y" |
| Kỹ năng nên bổ sung | `missing_skills` + `missing_preferred_skills` | Amber suggestion list (no "missing" framing) |
| Thông tin còn thiếu | derived from low `confidence_label` | "Hãy bổ sung kinh nghiệm làm việc vào hồ sơ" |
| Gợi ý cải thiện CV | derived from `risk_flags` | Reworded as positive actions |

### Candidate MUST NOT see
- Raw `fit_score` number
- `score_breakdown` component weights
- `confidence_label` raw value
- `relation_type` or `similarity` values from `related_matches`
- Recruiter-tone risk flags
- Pipeline metadata

### Candidate rank mapping
| `rank_label` | Candidate display |
|--------------|------------------|
| `high_fit` | ✅ Phù hợp cao |
| `medium_fit` | 🔶 Phù hợp vừa — có thể cải thiện |
| `low_fit` | 💡 Cần bổ sung thêm kỹ năng |
| `error` / `unknown` | ⏳ Đang xử lý |

---

## 6. Enum & Shape Definitions

### `rank_label`
| Value | Condition | Meaning |
|-------|-----------|---------|
| `high_fit` | fit_score ≥ 80 | Strong candidate match |
| `medium_fit` | 60 ≤ fit_score < 80 | Partial match with gaps |
| `low_fit` | fit_score < 60 | Weak match |
| `error` | pipeline failure | AI processing failed |
| `unknown` | no result | Not yet scored |

### `confidence_label`
| Value | Meaning | Score |
|-------|---------|-------|
| `high` | Both profiles extracted with high confidence | 1.0 |
| `medium` | One profile had partial extraction | 0.7 |
| `low` | Heuristic fallback or significant data missing | 0.4 |

### `ScoreBreakdownItem`
```jsonc
{
  "score": 0.83,     // float 0.0-1.0, raw component score
  "weight": 0.40,    // float 0.0-1.0, component weight (must sum to 1.0)
  "weighted": 33.2,  // float, score × weight × 100
  "detail": "5/6 kỹ năng bắt buộc"  // string, human-readable detail
}
```

### `score_breakdown` keys (canonical)
| Key | Component | Weight |
|-----|-----------|--------|
| `required_skill_coverage` | Required skills matched | 0.40 |
| `preferred_skill_coverage` | Preferred skills matched | 0.15 |
| `experience_fit` | Experience years vs range | 0.15 |
| `seniority_fit` | Seniority level match | 0.10 |
| `domain_relevance` | Domain keyword overlap | 0.10 |
| `confidence_adjustment` | Extraction confidence | 0.10 |

### `EvidenceItem`
```jsonc
{
  "source": "IT Skill Taxonomy",    // string — document title
  "excerpt": "...",                 // string — relevant text excerpt
  "score": 0.92,                   // float — similarity score
  "retrieval_method": "pgvector"   // "pgvector" | "static_fallback"
}
```

### `CandidateProfile` (intermediate, NOT persisted)
```jsonc
{
  "skills": ["PHP", "Laravel", "Docker"],
  "experience_years": 3.0,               // float | null
  "seniority": "mid",                    // "intern"|"fresher"|"junior"|"mid"|"senior"|"lead"|"principal" | null
  "education_level": "bachelor",         // "bachelor"|"master"|"phd"|"other" | null
  "domain_keywords": ["backend", "api"],
  "raw_summary": "...",
  "extraction_method": "llm",            // "llm" | "fallback"
  "extraction_confidence": "high",       // "high" | "medium" | "low"
  "notes": []
}
```

### `JobProfile` (intermediate, NOT persisted)
```jsonc
{
  "required_skills": ["Node.js", "PostgreSQL"],
  "preferred_skills": ["Kubernetes"],
  "seniority": "mid",
  "min_experience_years": 2.0,
  "max_experience_years": 5.0,
  "responsibilities": ["Design REST APIs"],
  "domain_keywords": ["backend", "microservices"],
  "extraction_method": "structured",     // "llm" | "structured" | "fallback"
  "extraction_confidence": "high",
  "notes": []
}
```

### `seniority` enum (shared)
`intern` | `fresher` | `junior` | `mid` | `senior` | `lead` | `principal`

### `retrieval_method` enum
`pgvector` | `static_fallback` | `none` | `demo_seed`

### `relation_type` enum (v1.1)
| Value | Meaning |
|-------|---------|
| `framework_of` | Target is a framework/library built on source |
| `prerequisite` | Source is prerequisite knowledge for target |
| `same_ecosystem` | Skills in the same technology ecosystem |
| `alternative_to` | Functionally equivalent alternatives |
| `related_tooling` | Complementary tooling often used together |
| `adjacent_skill` | Related domain knowledge, less direct |
| `superset_of` | Source fully contains target's capabilities |

---

## 7. Implementation Guidance

### FastAPI side
- Update `JobPayload` in `ai-service/app/schemas/matching.py` to accept Phase 1 fields
- `JobProfile.extraction_method` should be `"structured"` when skills come from structured DB fields

### Laravel side
- Update `AdminController::buildAiMatchPayload()` to send Phase 1 job fields
- `buildSanitizedAuditRecord()` whitelists 13 fields including `related_matches`
- `CandidateAdvisory::fromMatchResult()` translates `related_matches` into soft-phrased `related_strengths`

---

## 8. JD Quality Checker Contract (v1.1)

**Direction**: Laravel server-side, rendered inline on job creation form.
**Builder**: `App\Services\AI\JdQualityChecker::analyze()`

### Output shape
```jsonc
{
  "quality_score": 72,                   // int 0-100
  "quality_label": "good",               // "excellent" | "good" | "needs_improvement" | "poor"
  "issues": [                            // list[JdIssue] — problems detected
    {
      "field": "required_skills",         // which field has the issue
      "severity": "warning",             // "error" | "warning" | "info"
      "message": "Chưa chọn kỹ năng bắt buộc — AI sẽ không thể đánh giá chính xác."
    }
  ],
  "suggestions": [                       // list[str] — positive improvement tips
    "Thêm kỹ năng bắt buộc để AI so khớp CV chính xác hơn."
  ],
  "suggested_skills": {                  // optional inferred suggestions
    "required": ["PHP", "Laravel"],
    "preferred": ["Docker", "CI/CD"]
  },
  "suggested_seniority": "mid",          // inferred from title/description | null
  "suggested_experience": {              // inferred from seniority | null
    "min": 2,
    "max": 5
  }
}
```

### Quality label mapping
| `quality_label` | Score range | Meaning |
|----------------|-------------|--------|
| `excellent` | 85-100 | Well-structured JD, AI can match confidently |
| `good` | 65-84 | Usable JD, minor improvements possible |
| `needs_improvement` | 40-64 | Several missing fields, AI accuracy limited |
| `poor` | 0-39 | Insufficient data for meaningful AI matching |

### Issue severity
| `severity` | Meaning |
|------------|--------|
| `error` | Critical gap that severely limits AI accuracy |
| `warning` | Important but non-blocking issue |
| `info` | Minor suggestion for improvement |
