# Technical Slide Notes — Smart CV Matcher

> **Purpose**: Slide-ready notes for presentation preparation.
> **Last updated**: 2026-05-16
> **Rule**: Everything below is implemented. Roadmap items are explicitly labeled.

---

## 1. Project Summary (1 slide)

**Smart CV Matcher** — Hệ thống AI multi-agent hỗ trợ tuyển dụng, tự động phân tích CV, so khớp với Job Description, xếp hạng ứng viên, và giải thích kết quả.

| | |
|-|-|
| **Đầu vào** | CV ứng viên + Job Description có cấu trúc |
| **Đầu ra** | Shortlist xếp hạng + Giải thích + Cảnh báo rủi ro |
| **AI Pipeline** | 6 agent: Extraction → RAG → Matching → Explanation → Critic → Feedback |
| **Scoring** | Deterministic 6 thành phần, không phải LLM random |
| **Đặc biệt** | Explainable, fallback ở mọi tầng, human-in-the-loop |

---

## 2. Problem & Solution

### Vấn đề
- Recruiter nhận 50–100 CV mỗi vị trí → đọc thủ công mất 2–3 ngày
- Keyword filter quá thô — bỏ sót ứng viên có kỹ năng liên quan
- Quyết định không nhất quán giữa các recruiter
- Không có audit trail cho lý do shortlist

### Giải pháp
- AI tự động extract structured profiles từ CV và JD
- Scoring deterministic 6 thành phần — reproducible, transparent
- Skill relation graph: nhận ra TypeScript liên quan Java (same ecosystem)
- Mỗi score đi kèm giải thích: matched, missing, risk flags, breakdown
- Feedback loop: recruiter validate → hệ thống học dần

---

## 3. Architecture Slide Notes

```
                    ┌─────────────────┐
                    │  Demo Landing   │
                    │  Role Selection │
                    └────────┬────────┘
                             │
              ┌──────────────┴──────────────┐
              │                             │
     ┌────────▼────────┐          ┌─────────▼────────┐
     │   Candidate UI  │          │   Recruiter UI   │
     │   (Apply + CV)  │          │  (AI Shortlist)  │
     └────────┬────────┘          └─────────┬────────┘
              │                             │
              └──────────────┬──────────────┘
                             │
                    ┌────────▼────────┐
                    │ Laravel Backend │
                    │  (PHP 8.2)     │
                    └────────┬────────┘
                             │ POST /api/v1/match
                    ┌────────▼────────┐
                    │ FastAPI AI Svc  │
                    │ (Python 3.11)  │
                    │  6 Agents      │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │  PostgreSQL     │
                    │  + pgvector    │
                    └─────────────────┘
```

**Điểm nhấn**:
- 3 tầng tách biệt: UI → Backend → AI Service
- AI service stateless — kết quả persist ở Laravel
- pgvector cho RAG retrieval + fallback cascade

---

## 4. AI Pipeline Slide Notes

```
CV + JD → [1] ExtractorAgent → [2] RAGAgent → [3] MatcherAgent
         → [4] ExplainerAgent → [5] CriticAgent → [6] FeedbackReranker
         → Sanitized Result → Shortlist UI
```

| Agent | Vai trò | Kỹ thuật |
|-------|---------|----------|
| **ExtractorAgent** | Extract CandidateProfile + JobProfile | LLM (GPT-4o-mini JSON mode) + heuristic fallback |
| **RAGAgent** | Grounding bằng knowledge documents | pgvector similarity → keyword DB → static corpus |
| **MatcherAgent** | Tính fit_score deterministic | 6-component weighted formula + skill graph |
| **ExplainerAgent** | Tạo reasoning cho recruiter | Citation-aware, related-skill provenance |
| **CriticAgent** | Validate confidence, adjust edge cases | Rule-based validation |
| **FeedbackReranker** | Điều chỉnh nhẹ từ feedback | Bounded ±3/−5 pts, separate from canonical score |

**Key point**: LLM chỉ extract và explain — KHÔNG tạo score. Score là công thức deterministic.

---

## 5. Product Flow Slide Notes

### Candidate Flow
1. Chọn job → Nộp CV (upload hoặc form)
2. AI phân tích ngay → Hiện advisory mềm: "Phù hợp cao / Vừa / Cần cải thiện"
3. Nếu thiếu thông tin → AI yêu cầu bổ sung → Đánh giá lại
4. Ngôn ngữ tích cực: "Kỹ năng nên bổ sung" thay vì "Missing skills"

### Recruiter Flow
1. Dashboard → Chọn job → AI Shortlist
2. Danh sách xếp hạng: score badge + skill chips + risk flags
3. Expand → Chi tiết: 5 thanh breakdown, matched/missing/related skills
4. 🔬 X-Ray → Deep visualization: Score Card + Skill Graph + Timeline
5. 💬 Phản hồi → Đồng ý / Không đồng ý / Cần xem lại / Ghi chú

---

## 6. Key Implemented AI Features

### 6a. Skill Relation Graph (Graph-lite)
- Corpus: `skill_relations.json` — 7 loại quan hệ
- One-hop: TypeScript → Java (same_ecosystem, 35%)
- Two-hop: React → TypeScript → Node.js (chained, 40% credit)
- Partial credit: related match ≠ exact match — one-hop 80%, two-hop 40%
- **Kết quả**: Recruiter thấy tại sao ứng viên "gần phù hợp"

### 6b. Deterministic Scoring
- 6 thành phần, trọng số cố định (configurable per-job)
- `fit_score = Σ(component_score × weight × 100)`
- Cap tại 98 — không bao giờ 100% không có human validation
- Reproducible: cùng input → cùng score

### 6c. Retrieval-Augmented Grounding
- pgvector + OpenAI embeddings cho vector search
- 4-mode fallback cascade: pgvector → keyword DB → DB unavailable → static
- Auto-seed 6 knowledge documents on first boot

### 6d. PII Masking
- Email, phone, URL bị mask trước khi gửi LLM
- Pattern: `[EMAIL]`, `[PHONE]`, `[URL]`

---

## 7. AI Matching X-Ray

Trang visualization cho recruiter muốn hiểu sâu AI quyết định.

### 3 sections:

**1️⃣ AI Score Card**
- Donut chart: fit_score / 100
- 5 breakdown bars: weight + score + detail text
- Risk flags
- Pipeline version + timestamp

**2️⃣ AI Matching X-Ray Graph (SVG)**
- Node trái: Candidate (name)
- Node phải: Job (title)
- Nodes giữa: skills — color-coded:
  - 🟢 Xanh = matched (exact/synonym)
  - 🔵 Xanh dương = related match (dashed, with label: "same ecosystem 35%")
  - 🔴 Đỏ = missing required
  - 🟡 Cam = missing preferred
- Edges: connecting candidate → skills → job
- **Data source**: 100% từ persisted AI result fields — không phải decorative

**3️⃣ AI Processing Timeline**
- Vertical timeline: 6 steps
- Agent names (English): ExtractorAgent → RAGAgent → MatcherAgent → ExplainerAgent → CriticAgent → FeedbackReranker
- Mỗi step: compact human-readable summary
- Graceful degradation: legacy results without agent_trace show "không khả dụng"

---

## 8. Human-in-the-Loop Feedback

### Implemented
- 4 feedback types: 👍 Đồng ý, 👎 Không đồng ý, ⚠️ Cần xem lại, 📝 Ghi chú
- AJAX inline — không reload page
- Persist vào `ai_feedbacks` table
- Preset quick notes + free-text
- Existing feedback badge hiển thị trạng thái

### FeedbackReranker
- Đọc signals từ DB per-job
- Tính adjustment bounded: max +3pts, max −5pts
- **KHÔNG ghi đè canonical fit_score** — lưu riêng `feedback_adjustment`
- Cần tối thiểu 2 feedback entries mới activate
- Toggle on/off qua env var

### Chưa implement
- Learning-to-rank model training
- Feedback → evaluation metrics

---

## 9. Fallback / Resilience

**Nguyên tắc**: Không có agent nào được phép crash toàn pipeline.

| Tầng | Nếu fail | Hệ thống làm gì |
|------|----------|-----------------|
| LLM API | API key sai / timeout | Dùng heuristic extraction — deterministic, không cần LLM |
| Embeddings | API fail | Keyword search trong DB, hoặc static corpus |
| pgvector DB | Connection fail | In-memory static corpus (3 docs) |
| AI service | Service down | Shortlist UI hiện trạng thái lỗi rõ ràng, không crash |
| Data thiếu | CV trống / JD vague | Neutral score (0.5) + risk flag "dữ liệu không đủ" |

**Kết quả persist**: Chạy AI 1 lần → lưu JSONB → load lại ngay, không cần gọi AI lại.

---

## 10. Hơn Keyword Matching — Thế Nào?

| Keyword Matching | Smart CV Matcher |
|-----------------|-----------------|
| So trùng từ khóa | Hiểu "TypeScript liên quan Java" qua skill graph |
| Binary: có/không | Partial credit: one-hop 80%, two-hop 40% |
| Không giải thích | Mỗi score có breakdown 6 thành phần + risk flags |
| 1 tiêu chí | 6 tiêu chí: skills, experience, seniority, domain, confidence |
| Không fallback | 4-mode cascade: pgvector → keyword → DB down → static |
| Không truy vết | X-Ray: Score Card + Skill Graph + Agent Timeline |
| Không feedback | Human-in-the-loop: agree/disagree/flag/note → reranker |

---

## 11. Roadmap Only (NOT Implemented)

> [!WARNING]
> Các mục dưới đây chưa implement. Không trình bày như đã hoạt động.

| Item | Phase | Notes |
|------|-------|-------|
| Per-job scoring config UI | Next | Schema `jobs.scoring_config` sẵn, UI chưa có |
| Expanded knowledge corpus | Next | Cần curate thêm IT skill data |
| Learning-to-rank from feedback | Future | Cần volume feedback đủ lớn |
| GNN-based skill graph | Future | Cần graph data infrastructure |
| Fine-tuned SLM for extraction | Future | Cần labeled extraction dataset |
| LangGraph orchestration | Future | Migration effort significant |
| Multi-source RAG | Future | Web / papers retrieval |
| ASR / interview analysis | Future | Out of current product scope |
| CV layout vision parser | Future | Beyond current extraction approach |
