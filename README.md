# Smart CV Matcher - Level 5 Hackathon Scaffold

This repository is scaffolded to support a high-score AI demo:
- Laravel backend (existing project, MVC-first)
- FastAPI AI orchestrator (multi-agent workflow)
- PostgreSQL + pgvector (grounding and vector retrieval base)
- Docker Compose for one-command bring-up

## Quick Start

1. Optional: set API keys in shell
   - `OPENAI_API_KEY`
2. Start services
   - `docker compose up --build`
3. Backend:
   - `http://localhost:8000`
4. AI service:
   - `http://localhost:8001/docs`

## New Endpoint

- `POST /api/ml/ai-match` (Laravel)
  - body:
    - `candidate_id` (required)
    - `job_id` (required)
    - `include_reasoning` (optional, boolean)

## High-impact next steps (for judges)

- Connect RAG agent to real company/JD/Korean culture documents with pgvector.
- Build eval sheet (50-200 labeled CV-JD pairs) and report metrics.
- Add explainability UI panel showing evidence citations and agent trace.
- Add safe fallback to rule-based score when AI service is down.
