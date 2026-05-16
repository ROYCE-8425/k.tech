# Mức độ sử dụng AI được ban giám khảo đánh giá cao

Câu hỏi rất đúng trọng tâm. Trong các hackathon "Solve with AI" do doanh nghiệp tổ chức, ban giám khảo (đặc biệt là doanh nghiệp Hàn — vốn thực dụng) phân biệt rất rõ giữa **"dùng AI cho có"** và **"AI là core engine"**. Dưới đây là khung phân tầng và những hướng được đánh giá cao.

---

## 📊 Khung 5 cấp độ sử dụng AI (từ thấp đến cao)

### Level 1 — **AI as Wrapper** ❌ *Thường bị loại sớm*
Chỉ gọi API ChatGPT/Claude với prompt đơn giản, không có logic phía sau.

**Dấu hiệu:**
- "User nhập CV → gửi cho GPT-4 → in kết quả ra".
- Toàn bộ business logic nằm trong prompt.
- Không có data riêng, không có pipeline xử lý.

**Vì sao bị đánh giá thấp:** Ban giám khảo nghĩ ngay "tôi tự dùng ChatGPT cũng làm được, sao phải trả tiền cho team này?"

---

### Level 2 — **AI + Prompt Engineering** ⚠️ *Đạt mức trung bình*
Có prompt engineering, few-shot examples, chain-of-thought, structured output (JSON mode).

**Dấu hiệu:**
- Multi-step prompting (extract → analyze → recommend).
- Có system prompt được thiết kế kỹ.
- Output structured để integrate với UI.

**Vì sao chưa đủ:** Vẫn là "ChatGPT có UI đẹp". Bất kỳ developer nào học prompt engineering 1 tuần đều làm được.

---

### Level 3 — **AI + RAG / Tool Use** ✅ *Mức kỳ vọng tối thiểu cho top 30%*
Kết hợp LLM với data riêng (RAG), function calling, hoặc agent đơn giản.

**Dấu hiệu:**
- Vector database (Qdrant, Pinecone, pgvector) chứa data domain-specific (JD, company info, Korean business culture docs).
- Function calling để gọi external tools (crawl, search, calendar).
- Có embedding model riêng hoặc fine-tuned cho domain.

**Vì sao tốt hơn:** Có moat — data riêng. Ban giám khảo thấy được "tại sao team này build chứ không chỉ dùng ChatGPT".

---

### Level 4 — **AI + Agentic Workflow / Multi-agent** ✅✅ *Top 10-15%*
Hệ thống có nhiều AI agent phối hợp, có planning, có memory, có self-correction.

**Dấu hiệu:**
- Multiple specialized agents (Extractor Agent, Matcher Agent, Critic Agent, Reporter Agent).
- Có planning loop: agent tự quyết định step tiếp theo.
- Có self-reflection: agent đánh giá output của chính mình rồi refine.
- Persistent memory across sessions.
- Sử dụng framework như LangGraph, CrewAI, AutoGen — hoặc tự build orchestration.

**Vì sao nổi bật:** Đây là xu hướng 2025-2026 (Anthropic Claude Code, OpenAI Swarm, Google Antigravity). Ban giám khảo thấy team đang "ride the wave".

**Ví dụ áp dụng cho 3 bài toán:**
- **CV Matcher:** Extractor Agent đọc CV → Verifier Agent crawl GitHub check → Matcher Agent so với JD → Explainer Agent generate report → Critic Agent review trước khi gửi HR.
- **Onboarding:** Calendar Agent monitor lịch → Context Agent gather info → Coach Agent generate proactive tip → Translator Agent rewrite theo formality level.
- **Interview Coach:** Question Agent generate câu hỏi → Interviewer Agent đóng vai → Evaluator Agent chấm điểm → Planner Agent lập learning path.

---

### Level 5 — **AI + Specialized Models / Hybrid Architecture** ✅✅✅ *Top 5%*
Kết hợp LLM với các mô hình AI chuyên biệt khác (GNN, CV, ASR, fine-tuned SLM).

**Dấu hiệu:**
- Không chỉ dựa vào LLM API — có model riêng giải quyết một bài toán cụ thể tốt hơn LLM.
- Hybrid pipeline: LLM cho reasoning + small specialized model cho task-specific.
- Có thể là: GNN cho graph reasoning, Whisper + prosody model cho voice analysis, MediaPipe cho facial cue, fine-tuned SLM cho domain Korean-Vietnamese.
- Có thể train/fine-tune model nhỏ (LoRA, embeddings) trên data riêng.

**Vì sao top 5%:** Cho thấy team **hiểu sâu** AI, không chỉ là "API caller". Đây là dấu hiệu của team có thể build defensible product, không phải prototype tạm bợp.

**Ví dụ áp dụng:**
- **CV Matcher:** LLM extract + GNN trên skill graph để reason về prerequisite skills + small classifier fine-tuned phát hiện CV inflation pattern.
- **Onboarding:** LLM cho generation + small classifier detect formality level (한국어 존댓말 detection) + recommendation model trên cultural KG.
- **Interview Coach:** LLM cho feedback + Whisper cho transcription + small prosody model (pace, confidence) + MediaPipe cho visual cue + GNN cho prerequisite-aware learning path.

---

## 🎯 Các yếu tố AI được ban giám khảo đánh giá CAO

### 1. **Explainability — AI giải thích được "tại sao"**
Doanh nghiệp Hàn cực coi trọng việc defend quyết định (đặc biệt HR, tuyển dụng — có liên quan đến luật lao động).

- ❌ "AI rank ứng viên A cao nhất" → không thuyết phục.
- ✅ "AI rank A cao nhất vì: (1) match 85% skill JD, (2) có 2/3 prerequisite skills cho senior level, (3) experience trajectory tương đồng top performer hiện tại của công ty" → thuyết phục.

**Cách implement:** Citation, attention visualization, chain-of-thought visible, structured reasoning trace.

---

### 2. **Grounding — AI dựa trên data thật, không hallucinate**
Hackathon doanh nghiệp ghét nhất AI "tự tin nói sai".

- ❌ AI tự generate ra một "Korean business norm" không có nguồn.
- ✅ AI trả lời + cite nguồn: "Theo onboarding handbook của công ty + thread Slack #culture 3 tháng trước, sếp 김 thường response sau 11pm là OK".

**Cách implement:** RAG với citation, knowledge graph truy vết, evidence link.

---

### 3. **Personalization — AI thích ứng theo từng user**
Demo dynamic adaptation đánh điểm cực cao.

- ❌ Một interview coach trả lời ai cũng giống ai.
- ✅ Coach học từ 3 buổi trước → biết user yếu System Design → focus drill vào đó → adjust difficulty.

**Cách implement:** Persistent memory, user embedding, online learning, dynamic prompting.

---

### 4. **Multi-modal — AI xử lý nhiều loại input**
Đa số đội chỉ làm text. Đội nào làm multi-modal nhảy lên top ngay.

- Voice (Whisper, Azure Speech).
- Image / Video (CLIP, MediaPipe, Gemini Vision).
- Document (layout-aware parsing với LayoutLM, Docling).

**Lưu ý:** Đừng "nhồi" multi-modal cho có. Chỉ thêm khi nó **giải quyết pain point thật**. Ví dụ interview coach mà thiếu voice analysis thì khó thuyết phục.

---

### 5. **Real-time / Streaming**
Demo realtime tạo wow factor cực mạnh trên stage.

- Streaming response (token by token) thay vì chờ load.
- Live transcription khi user nói.
- Live update khi user typing.

**Lưu ý:** Latency dưới 2 giây là chuẩn 2026. Trên 5 giây ban giám khảo sẽ bored.

---

### 6. **Safety & Guardrails — AI có ranh giới**
Đây là điểm mà nhiều đội bỏ qua nhưng ban giám khảo doanh nghiệp Hàn cực coi trọng.

- Phát hiện và từ chối câu hỏi off-topic.
- Không hallucinate fact về công ty/luật.
- PII redaction khi xử lý CV.
- Bias detection (ví dụ CV matcher không discriminate theo giới/tuổi/vùng miền).

**Cách implement:** LlamaGuard, content moderation API, structured constraint, eval suite.

---

### 7. **Evaluation — Team chứng minh AI hoạt động tốt bằng số liệu**
Đa số team chỉ "demo thấy chạy được". Top team có **benchmark riêng**.

- ❌ "AI của chúng tôi rất chính xác".
- ✅ "Trên test set 200 CV-JD pair được expert HR đánh nhãn, precision@5 đạt 0.87, baseline GPT-4 zero-shot chỉ 0.62".

**Cách implement:** Build small eval set (50-100 sample), so sánh với baseline (raw GPT-4), report metric cụ thể.

---

## 🔥 Công thức "AI level" cho hackathon top 5%

Một presentation đạt top 5% thường có cấu trúc:

```
Core AI Engine = LLM (reasoning) 
              + RAG hoặc KG (grounding) 
              + Specialized model (1-2 cái cho task cụ thể)
              + Agentic orchestration (multi-step)
              + Eval suite (chứng minh hiệu quả)
```

Cụ thể cho 3 bài toán:

**CV Matcher top 5%:**
- LLM extract structured data từ CV
- Skill KG hoặc taxonomy (ESCO/O*NET) cho grounding
- GNN hoặc embedding model fine-tuned cho match
- Verifier agent crawl evidence
- Eval trên 100 CV-JD pair có ground truth

**Onboarding top 5%:**
- LLM cho generation
- RAG trên Korean business culture corpus + company-specific docs
- Formality classifier nhỏ
- Proactive agent monitor calendar/Slack
- Eval bằng user study mini (5-10 người thử trong 1 tuần)

**Interview Coach top 5%:**
- LLM cho feedback content
- Whisper + prosody model cho voice
- MediaPipe cho visual cue
- Concept graph cho personalized path
- Eval: improvement score sau N session

---

## ⚠️ Những "anti-pattern" làm mất điểm AI

1. **"AI sticker":** Gán mác AI vào feature không cần AI (ví dụ checklist tĩnh gọi là "AI checklist").
2. **Over-engineering:** Dùng 5 model khi 1 model giải quyết được. Ban giám khảo hỏi "tại sao phức tạp vậy?" → trả lời không nổi.
3. **Black box demo:** AI làm gì đó "magic" nhưng không giải thích được. Ban giám khảo Hàn sẽ hỏi sâu kỹ thuật.
4. **Hardcoded demo:** Demo chỉ chạy được với 1-2 input chuẩn bị trước. Ban giám khảo có thể yêu cầu test với input bất kỳ.
5. **Latency cao:** AI mất 30 giây mới phản hồi → trên stage sẽ cực awkward.
6. **Không có fallback:** API fail giữa demo → cả hệ thống chết. Top team luôn có offline cache hoặc graceful degradation.

---

## 📌 Tóm gọn — Checklist top 5% về mặt AI

| Yếu tố | Có | Không |
|---|---|---|
| Không chỉ là wrapper quanh ChatGPT | ☐ | |
| Có data/knowledge riêng (RAG hoặc KG) | ☐ | |
| Có ít nhất 1 specialized model ngoài LLM | ☐ | |
| Agentic workflow (multi-step có planning) | ☐ | |
| Output có explanation rõ ràng | ☐ | |
| Có guardrails / safety | ☐ | |
| Có benchmark/eval số liệu cụ thể | ☐ | |
| Demo real-time, latency dưới 3s | ☐ | |
| Có fallback khi AI fail | ☐ | |
| Korean-specific element rõ ràng | ☐ | |

Đạt 7/10 ô trở lên là đã ở vùng top 10%. Đạt 9/10 với câu chuyện kể tốt là vùng top 5%.
