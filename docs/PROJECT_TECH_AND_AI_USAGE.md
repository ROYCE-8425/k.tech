# Project Technology And AI Usage

## 1. Project Overview
**Smart CV Matcher** la mot he thong AI-assisted CV-to-JD matching.

Muc tieu chinh:
- giup **recruiter** shortlist ung vien nhanh hon
- giup **candidate** hieu muc do phu hop va cai thien ho so
- giu viec cham diem **minh bach, explainable, deterministic**
- tranh bien he thong thanh chatbot hoac HRM full-suite

He thong nay khong de AI quyet dinh tuyen dung thay con nguoi.
AI chi dong vai tro:
- phan tich
- chuan hoa
- so khop
- giai thich
- canh bao rui ro
- ho tro recruiter ra quyet dinh

---

## 2. Core Product Flows

### Candidate Flow
Candidate co the:
1. vao demo mode
2. xem danh sach job
3. mo chi tiet job
4. apply bang CV hoac form nhanh
5. nhan AI advisory / follow-up neu co
6. bo sung thong tin de AI danh gia lai

### Recruiter Flow
Recruiter co the:
1. vao dashboard
2. xem danh sach job
3. mo **AI Shortlist**
4. xem score, matched skills, missing skills, risk flags
5. mo **AI Matching X-Ray**
6. mo **AI Decision Lab**
7. de lai feedback: agree / disagree / note / flag
8. tao job moi va dung **JD Quality Checker**

---

## 3. Technology Stack

### Backend Web
- **Laravel**
- Blade views
- SQLite cho local dev
- PostgreSQL la huong runtime/deployment chinh

### AI Service
- **FastAPI**
- Pydantic schemas
- OpenAI / Gemini / xAI provider abstraction
- async orchestration
- fallback-safe architecture

### Database / Retrieval
- PostgreSQL
- pgvector readiness / runtime path
- `knowledge_documents` corpus
- static fallback corpus neu DB/vector unavailable

### Frontend/UI
- Blade templates
- Alpine.js cho interactive recruiter UI
- SVG-based AI Matching X-Ray visualization
- recruiter-facing comparison/lab views

### Deployment
- Docker Compose ho tro
- VPS deployment path da duoc chuan bi
- AI service va Laravel app tach vai tro ro rang

---

## 4. Main AI Architecture

AI pipeline hien tai di theo huong hybrid, explainable, recruiter-oriented.

### Pipeline
1. **ExtractorAgent**
2. **RAGAgent / Knowledge Retriever**
3. **MatcherAgent**
4. **ExplainerAgent**
5. **CriticAgent**
6. **FeedbackReranker** (foundation / bounded adjustment)

### High-Level Flow
CV / Candidate Profile
-> structured extraction
-> skill normalization
-> domain & seniority normalization
-> retrieval grounding
-> deterministic weighted matching
-> explanation
-> critic validation
-> optional feedback-aware comparison/rerank layer

---

## 5. Where AI Is Used

### 5.1 Candidate / CV Understanding
AI duoc dung de:
- doc candidate profile / CV text
- trich xuat skill
- trich xuat years of experience
- trich xuat seniority
- trich xuat domain keywords
- chuan hoa du lieu candidate thanh profile co cau truc

Neu LLM unavailable:
- fallback heuristic extraction van hoat dong

### 5.2 Job Description Understanding
AI system khong chi doc JD tho.
No uu tien:
- `required_skills`
- `preferred_skills`
- `seniority`
- `min_experience_years`
- `max_experience_years`
- `scoring_config`
- `ai_recruiter_notes`

Neu du lieu job da structured:
- dung truc tiep
- khong phu thuoc hoan toan vao prompt parsing

Neu du lieu job thieu:
- fallback sang extraction / heuristic

### 5.3 Skill Matching
AI system khong chi keyword matching.

Hien tai he thong dung:
- exact match
- synonym normalization
- graph-lite related skill matching
- bounded two-hop reasoning

Vi du:
- `nodejs` -> `Node.js`
- `postgres` -> `PostgreSQL`
- `PHP` co the related voi `Laravel`
- `MySQL` co the related voi `PostgreSQL`

Dieu nay giup he thong:
- khong cham 0 ngay khi skill khong trung tuyet doi
- hieu phan nao kha nang chuyen doi ky nang

### 5.4 Deterministic Scoring
**LLM khong truc tiep cham diem cuoi.**

`fit_score` duoc tinh bang code, khong phai do model tu "doan".

Cac thanh phan chinh:
- required skill coverage
- preferred skill coverage
- experience fit
- seniority fit
- domain relevance
- confidence adjustment

Ngoai ra con co:
- graph-lite related-skill partial credit
- feedback-derived bounded rerank layer trong comparison/lab mode

Loi ich:
- on dinh
- audit duoc
- explainable
- phu hop cho recruiter trust

### 5.5 Explanation and Risk Analysis
AI duoc dung de tao phan giai thich de doc hon cho recruiter va candidate.

Recruiter thay:
- matched skills
- missing skills
- related matches
- score breakdown
- confidence
- risk flags
- processing trace

Candidate thay:
- match level
- strengths
- suggested skills
- missing info
- improvement tips

Tuc la AI output duoc tach theo audience.

### 5.6 Retrieval / RAG Usage
He thong co retrieval layer de grounding explanation va ho tro reasoning.

Retrieval modes hien tai:
- `pgvector`
- `fallback_db_no_embedding`
- `fallback_db_unavailable`
- `fallback_static`

RAG duoc dung de:
- grounding
- evidence
- guideline support
- retrieval metadata

Neu DB/vector chua san sang:
- static fallback corpus van giu system chay duoc

### 5.7 Human-in-the-Loop Feedback
Recruiter co the phan hoi vao AI result bang:
- agree
- disagree
- note
- flag

Feedback duoc luu trong `ai_feedbacks`.

Vai tro:
- chung minh he thong khong de AI quyet dinh mot chieu
- tao nen tang cho future learning / reranking
- ho tro story "AI assists, human decides"

### 5.8 AI Matching X-Ray
Day la recruiter-facing explainability screen.

No cho recruiter thay:
- **AI Score Card**
- **AI Matching X-Ray Graph**
- **AI Processing Timeline**

Nguon du lieu:
- `score_breakdown`
- `matched_skills`
- `missing_skills`
- `missing_preferred_skills`
- `related_matches`
- `risk_flags`
- `agent_trace`

Muc tieu:
- cho thay AI dang reasoning that
- khong chi tra ra mot con so

### 5.9 AI Decision Lab
Day la man hinh comparison de chung minh he thong tot hon keyword matching.

Cac mode hien tai:
- Baseline
- Graph One-Hop
- Graph Two-Hop
- Feedback-Aware

Decision Lab giup recruiter thay:
- baseline exact/synonym se cho ket qua gi
- graph reasoning cong them gi
- feedback-aware logic thay doi gi
- canonical score van duoc giu nguyen

---

## 6. Reliability and Fallback Design

He thong duoc thiet ke de **khong chet khi AI gap loi**.

### Khi AI service unavailable
- candidate apply van luu duoc
- recruiter van xem duoc seeded shortlist
- user nhan duoc message ro rang
- JD checker van hoat dong neu la rule-based path

### Khi provider loi
- fallback heuristic extraction
- confidence giam
- risk flags phan anh degraded mode

### Khi DB / pgvector loi
- retrieval fallback van chay
- health endpoint bao ro degraded reason

---

## 7. Main AI-Related Data Structures

### Persisted AI result
AI result duoc luu trong:
- `applications.ai_match_result`

Day la sanitized persistence, khong luu raw prompt/debug nang.

### Knowledge Corpus
- `knowledge_documents`

### Recruiter Feedback
- `ai_feedbacks`

### Structured Job Fields
- `required_skills`
- `preferred_skills`
- `seniority`
- `min_experience_years`
- `max_experience_years`
- `scoring_config`
- `ai_recruiter_notes`

---

## 8. Health and Runtime Visibility

AI service co health/status endpoint de kiem tra:
- provider nao dang active
- model nao dang dung
- DB co reachable khong
- pgvector co available khong
- embeddings co ready khong
- retrieval mode hien tai la gi
- fallback state ra sao

Dieu nay giup he thong:
- de deploy hon
- de debug hon
- minh bach hon khi trinh dien

---

## 9. Why This Is More Than Keyword Matching

Diem khac biet cua he thong:
- khong chi so trung keyword
- co structured extraction
- co normalization
- co graph-lite related skill reasoning
- co deterministic scoring
- co retrieval grounding
- co explanation
- co critic / risk layer
- co recruiter feedback loop
- co X-Ray va Decision Lab de giai thich quyet dinh

Noi ngan gon:
**day la mot hybrid AI recruitment engine, khong phai chi la web tuyen dung goi LLM mot lan.**

---

## 10. Current Boundaries

Nhung gi he thong da lam:
- recruiter shortlist
- candidate advisory
- explainable AI scoring
- graph-lite reasoning
- human-in-the-loop feedback
- comparison modes
- X-Ray visualization

Nhung gi chua coi la hoan tat production:
- full learning-to-rank model
- deep graph reasoning / graph DB
- full research-grade evaluation suite
- advanced benchmark claims
- enterprise HRM expansion

---

## 11. Suggested Use In Slides

Khi lam slide, nen dung file nay cho:
- kien truc tong quan
- pipeline AI
- recruiter flow
- candidate flow
- explainability / X-Ray
- Decision Lab
- fallback / resilience
- why this beats keyword matching
