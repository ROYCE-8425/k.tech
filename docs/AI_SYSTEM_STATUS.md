# AI System Status — Smart CV Matcher

> **Status**: Demo-ready MVP (`v2.0-demo`)
> **Last updated**: 2026-05-16
> **Governing spec**: `skills/OPENSPEC.md`

---

## 1. Product Identity

| Attribute | Value |
|-----------|-------|
| **Product** | Smart CV Matcher |
| **Primary use case** | Recruiter AI shortlist — ranked candidate matching |
| **Supporting use case** | Candidate advisory — soft fit indication and improvement hints |
| **Category** | Explainable AI-assisted recruitment decision support |
| **Demo target** | Korean companies hiring IT talent in Vietnam |
| **Not in scope** | Interview management, payroll, enterprise HR workflows |

---

## 2. Implemented Features (Current)

| Feature | Status | Files |
|---------|--------|-------|
| LLM structured extraction (CandidateProfile, JobProfile) | ✅ Implemented | `extractor.py` |
| Heuristic fallback extraction | ✅ Implemented | `extractor.py` |
| PII masking before LLM | ✅ Implemented | `extractor.py` |
| Skill normalization (synonym → canonical) | ✅ Implemented | `normalizer.py`, `skill_synonyms.json` |
| Skill relation graph (one-hop + two-hop) | ✅ Implemented | `normalizer.py`, `skill_relations.json` |
| Domain keyword taxonomy | ✅ Implemented | `normalizer.py`, `domain_keywords.json` |
| Seniority normalization + gap scoring | ✅ Implemented | `normalizer.py`, `seniority_rules.json` |
| OpenAI embeddings + pgvector retrieval | ✅ Implemented | `retriever.py` |
| Static fallback corpus | ✅ Implemented | `retriever.py` |
| Corpus auto-seed + embedding backfill | ✅ Implemented | `retriever.py` |
| 6-component deterministic weighted scorer | ✅ Implemented | `agents.py` MatcherAgent |
| Related-skill partial credit (one-hop 80%, two-hop 40%) | ✅ Implemented | `agents.py` MatcherAgent |
| ExplainerAgent (citation-aware rationale) | ✅ Implemented | `agents.py` |
| CriticAgent (confidence validation) | ✅ Implemented | `agents.py` |
| FeedbackReranker (bounded ±3/−5) | ✅ Implemented | `feedback_reranker.py` |
| Match orchestrator (6-agent pipeline) | ✅ Implemented | `orchestrator.py` |
| Recruiter shortlist UI | ✅ Implemented | `ai-shortlist.blade.php` |
| AI Matching X-Ray (Score Card + Graph + Timeline) | ✅ Implemented | `ai-xray.blade.php` |
| Multi-Agent Scoring Council (Phase 20) | ✅ Implemented | `GptScoringService.php`, `ai-xray.blade.php` |
| Recruiter feedback capture (agree/disagree/flag/note) | ✅ Implemented | `ai-shortlist.blade.php`, `AdminController` |
| Candidate advisory (soft-language after apply) | ✅ Implemented | `jobs/show.blade.php` |
| AI follow-up form (missing info prompt) | ✅ Implemented | `jobs/show.blade.php` |
| JD quality checker | ✅ Implemented | `post-job.blade.php`, `JdQualityChecker` |
| Sanitized audit-safe persistence | ✅ Implemented | `AdminController::buildSanitizedAuditRecord()` |
| Demo landing with role selection | ✅ Implemented | `demo/landing.blade.php` |
| Demo reset | ✅ Implemented | `DemoSeeder`, demo reset route |
| AI refresh (re-run single application) | ✅ Implemented | `ai-refresh` route |

---

## 3. Candidate Flow (Implemented)

```
Demo Landing → "Vào vai Ứng viên" → auto-login
    → Job Listing → Select Job → View JD
    → Apply: Upload CV or Form-based CV creation
    → AI processes immediately on submit
    → Advisory result: soft-language score, matched/missing skills, improvement hints
    → If AI detects missing info → follow-up form (phone, skills, experience, etc.)
    → Submit follow-up → AI re-evaluates with enriched data
```

**Key behaviors**:
- Candidate sees `fit_score / 10` (not raw 0–100) with Vietnamese labels: "Xuất sắc", "Tốt", "Khá", "Trung bình"
- Missing skills shown as "Kỹ năng nên bổ sung" (positive framing, not "missing")
- Candidate does NOT see: raw score, score breakdown weights, confidence label, relation_type, similarity values, risk flags

---

## 4. Recruiter Flow (Implemented)

```
Demo Landing → "Vào vai Nhà tuyển dụng" → auto-login
    → Dashboard → Select Job
    → Job page sidebar → "🤖 AI Shortlist" button
    → AI Shortlist: ranked candidates with:
        - fit_score badge (color-coded: ≥80 green, 60-79 amber, <60 red)
        - rank_label + confidence_label pills
        - matched/missing/missing_preferred skills (color-coded chips)
        - related_matches (blue chips with provenance)
        - risk_flags (amber warning blocks)
        - score_breakdown (5 component progress bars with weights)
        - pipeline + retrieval metadata
    → Per-candidate actions:
        - "🔬 X-Ray" → deep AI decision visualization
        - "🔄 Tính lại AI" → re-run pipeline for this application
        - "💬 Phản hồi" → agree / disagree / flag / note
    → Post Job form:
        - "🤖 Kiểm tra chất lượng JD" → quality score + issue list + suggestions
```

---

## 5. AI Architecture (Implemented)

```
┌──────────────────────────────────────────────────────────┐
│  Laravel Backend (PHP 8.2)                                │
│    ├── AdminController — shortlist, refresh, X-Ray        │
│    ├── AICVMatcherController — API bridge to AI service   │
│    ├── JdQualityChecker — server-side JD validation       │
│    └── Persistence: applications.ai_match_result (JSONB)  │
├──────────────────────────────────────────────────────────┤
│  FastAPI AI Service (Python 3.11)                         │
│    ├── POST /api/v1/match — main matching endpoint        │
│    ├── GET /api/v1/health — health check                  │
│    ├── MatchOrchestrator — 6-agent coordinator            │
│    ├── ExtractorAgent — LLM + heuristic extraction        │
│    ├── RAGAgent → KnowledgeRetriever (pgvector/fallback)  │
│    ├── MatcherAgent — deterministic weighted scorer       │
│    ├── ExplainerAgent — citation-aware rationale          │
│    ├── CriticAgent — confidence validation                │
│    └── FeedbackReranker — bounded feedback adjustment     │
├──────────────────────────────────────────────────────────┤
│  PostgreSQL + pgvector                                    │
│    ├── applications (ai_match_result JSONB)                │
│    ├── ai_feedbacks (feedback signals)                    │
│    └── knowledge_documents (embedding corpus)             │
└──────────────────────────────────────────────────────────┘
```

---

## 6. AI Service Pipeline

The `MatchOrchestrator.run()` executes 6 agents sequentially:

| Step | Agent | Input | Output | Fallback |
|------|-------|-------|--------|----------|
| 1 | **ExtractorAgent** | CandidatePayload, JobPayload | CandidateProfile, JobProfile | Heuristic keyword extraction |
| 2 | **RAGAgent** | JobProfile, job raw data | Evidence list, retrieval_method | Static in-memory corpus |
| 3 | **MatcherAgent** | CandidateProfile, JobProfile, scoring_config | fit_score, breakdown, matched/missing, related_matches, risk_flags | Neutral scores for missing data |
| 4 | **ExplainerAgent** | Matching result, evidence, profiles | Reasoning list (human-readable) | Minimal reason list |
| 5 | **CriticAgent** | fit_score, reasoning | Adjusted score, critic notes | Pass-through |
| 6 | **FeedbackReranker** | job_id, fit_score, confidence | FeedbackAdjustment (separate, bounded ±3/−5) | No adjustment |

**Pipeline version**: `v2.0`
**LLM provider**: OpenAI (GPT-4o-mini via structured JSON mode)
**Embedding model**: `text-embedding-3-small`

---

## 7. Matching / Scoring Logic

**Type**: Deterministic weighted hybrid scoring. LLM does NOT generate the score.

| Component | Weight | Method |
|-----------|--------|--------|
| Required skill coverage | 40% | Exact + synonym + related (partial credit) |
| Preferred skill coverage | 15% | Exact + synonym + related (partial credit) |
| Experience fit | 15% | Gaussian proximity to JD range |
| Seniority fit | 10% | Ordinal gap scoring (7-level scale) |
| Domain relevance | 10% | Keyword overlap with title-based enrichment |
| Confidence adjustment | 10% | Extraction confidence average, penalized by indirect-match ratio |

**Related-skill partial credit**:
- One-hop match: `similarity × 0.80` credit
- Two-hop match: `similarity × 0.40` credit (halved)
- Credit hierarchy: exact/synonym > one-hop > two-hop

**Score cap**: `min(98.0, total)` — prevents 100% without human validation.

---

## 8. Knowledge / Retrieval / Graph Reasoning

### RAG Retrieval (implemented)
- **pgvector mode**: OpenAI embeddings → PostgreSQL vector similarity search
- **Fallback modes** (automatic cascade):
  1. `pgvector` — full vector similarity
  2. `fallback_db_no_embedding` — DB available, embeddings unavailable, keyword search
  3. `fallback_db_unavailable` — DB connection failed
  4. `fallback_static` — in-memory static corpus (3 documents)
- **Corpus**: 6 seed documents auto-inserted on first boot, with embedding backfill

### Skill Normalization (implemented)
- Static JSON corpus: `skill_synonyms.json` (alias → canonical mapping)
- Deterministic: same input always produces same canonical form
- No ML/fuzzy matching — purely alias-based

### Skill Relation Graph (implemented — graph-lite, not GNN)
- Static JSON corpus: `skill_relations.json`
- 7 relation types: `framework_of`, `prerequisite`, `same_ecosystem`, `alternative_to`, `related_tooling`, `adjacent_skill`, `superset_of`
- One-hop and two-hop traversal (two-hop = chained relations)
- Used for partial credit in MatcherAgent, NOT for scoring directly

### Domain Classification (implemented)
- Static JSON corpus: `domain_keywords.json`
- Title-based domain keyword enrichment

### NOT implemented
- GNN-based graph reasoning
- Dynamic corpus management UI
- Skill knowledge graph with learned embeddings

---

## 9. Feedback Loop / Human-in-the-Loop

### Recruiter feedback capture (implemented)
- 4 feedback types: `agree`, `disagree`, `flag`, `note`
- Persisted in `ai_feedbacks` table (PostgreSQL)
- Inline UI on AI Shortlist page — per-candidate, AJAX-based
- Preset quick notes + free-text
- Feedback badge shows existing state

### FeedbackReranker (implemented)
- Reads aggregated signals from `ai_feedbacks` per job
- Computes bounded adjustment: max +3pts boost, max −5pts penalty
- **NEVER overwrites canonical fit_score** — stored separately as `feedback_adjustment`
- Requires minimum 2 feedback entries to activate
- Can be disabled via `FEEDBACK_RERANK_ENABLED=false`

### NOT implemented
- Learning-to-rank model training from feedback
- Feedback-derived evaluation metrics (NDCG, precision@k)
- A/B comparison between pipeline versions

---

## 10. Reliability / Fallback / Health

| Layer | Failure Mode | Fallback Behavior |
|-------|-------------|-------------------|
| LLM API (OpenAI) | API key missing or call fails | Heuristic keyword extraction (deterministic) |
| Embeddings | API fails or unavailable | Keyword-based DB search or static corpus |
| pgvector / DB | Connection failure | Static in-memory corpus (3 docs) |
| Corpus empty | No knowledge_documents | Auto-seed on first boot (6 docs) |
| AI service down | FastAPI unreachable | Shortlist shows clear error state, no crash |
| Missing AI result | No persisted result for app | "Chưa có kết quả AI" state in UI |
| Low confidence | Heuristic extraction used | Risk flag + `confidence_label=low` |
| Persist not allowed | No `application_id` | Return result without persistence |

**Health endpoint**: `GET /api/v1/health` — reports service status, DB connectivity, embedding availability, corpus counts.

---

## 11. Deployment / Runtime

| Component | Runtime | Port |
|-----------|---------|------|
| Laravel backend | PHP 8.2 + Nginx | 80 (VPS) |
| FastAPI AI service | Python 3.11 + Uvicorn | 8001 |
| PostgreSQL | 15+ with pgvector | 5432 |

**VPS**: `160.191.237.64` (Ubuntu)
**Demo data**: `DemoSeeder` — 2 candidates, 4 jobs, 4 applications (2 with pre-seeded AI results)
**Demo credentials**: `demo-recruiter@smartcv.demo` / `demo-candidate@smartcv.demo` (password: `demo1234`)
**Demo reset**: Landing page → "Reset Demo về dữ liệu gốc"

---

## 12. Implemented Now vs Approved Next vs Future

### ✅ Implemented Now (v2.0-demo)

- LLM structured extraction + heuristic fallback
- PII masking
- Skill normalization (static synonym corpus)
- Skill relation graph (one-hop + two-hop, static JSON)
- Domain keyword taxonomy + title enrichment
- Seniority normalization + gap scoring
- 6-component deterministic weighted scorer
- Related-skill partial credit
- pgvector RAG retrieval + 4-mode fallback cascade
- Corpus auto-seed + embedding backfill
- ExplainerAgent (citation-aware, related-skill provenance)
- CriticAgent (confidence validation, edge-case adjustment)
- FeedbackReranker (bounded, explainable, separate from canonical score)
- Recruiter shortlist UI (ranked, expandable, color-coded)
- AI Matching X-Ray (Score Card + Skill Graph + Processing Timeline)
- Multi-Agent Scoring Council (Advisory Layer: SkillGraph, ExperienceFit, DomainTrend, RiskCritic, Consensus)
- Controlled Domain Trend Lenses (Static JSON context files, NOT live internet intelligence)
- Recruiter feedback capture (agree/disagree/flag/note)
- Candidate advisory (soft Vietnamese labels, follow-up form)
- JD quality checker (inline on post-job form)
- Sanitized audit-safe persistence (13 fields in JSONB)
- Demo landing + role selection + demo reset
- AI refresh (re-run single application)

### 🟡 Approved Next (schema ready, UI/pipeline not connected)

- Per-job scoring config overrides (`jobs.scoring_config` column exists)
- Expanded static knowledge corpus
- Feedback → evaluation dataset pipeline
- Metric tracking (NDCG, precision@k)
- Confidence-adjusted visual presentation
- Skill normalization improvements (broader corpus)

### 🔮 Future (not in MVP, requires significant investment)

- GNN-based skill graph reasoning
- Fine-tuned SLM for extraction
- Learning-to-rank model from feedback
- LangGraph orchestration migration
- Dynamic corpus management UI
- Multi-source RAG (web, papers)
- ASR / interview voice analysis
- CV layout computer vision parser
