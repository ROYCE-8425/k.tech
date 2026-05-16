# Smart CV Matcher — Demo Guide & Checklist

> **For team use during demo prep, presentation rehearsal, and live demos.**
> Product story: "KTC receives hundreds of CVs each week. Smart CV Matcher uses AI to
> automatically analyze CVs, match them to Korean-company job descriptions, rank candidates,
> and explain why they fit — replacing manual CV reading."
>
> Demo scope is narrowed: only the AI CV matching workflow is visible.

---

## ⚡ 5-Minute Pre-Demo Quick Start

> Run this in order if you're starting from scratch, 5 minutes before demo.

```bash
# 1. Start services (if not already running via Docker)
cd backend && php artisan serve &              # Laravel on :8000
cd ai-service && uvicorn app.main:app --port 8001 &  # AI on :8001

# 2. Seed everything
cd backend
php artisan db:seed
php artisan db:seed --class=EvalDatasetSeeder

# 3. Pre-warm: run eval to persist AI results (so shortlist page loads instantly)
php artisan eval:shortlist --seed

# 4. Verify
curl -s http://localhost:8001/docs | head -5   # Should show FastAPI HTML

# 5. Open browser to:
#    http://localhost:8000/admin/jobs
#    → Find "Backend Developer (Laravel/PHP)" → click "Ứng viên" → click "🤖 AI Shortlist"
```

---

## 🎯 Recommended Demo Paths

### Path A: The Money Shot — "Backend Developer (Laravel/PHP)" ⭐

This is the **best job to demo**. It has the clearest signal spread:

| Candidate | Expected | Why |
|-----------|----------|-----|
| **Eval - Phạm Backend Senior** | 🟢 High fit | Laravel, PHP, MySQL, Redis, Docker — near-perfect match |
| Trần Quốc Huy (seeded) | 🟢 High fit | Laravel, PHP, MySQL, Redis, Docker — original seeded candidate |
| Eval - Vũ Frontend Junior | 🔴 Low fit | React/JS only — completely wrong stack |
| Eval - Đức Marketing | 🔴 Low fit | Marketing skills — wrong domain entirely |

**What this shows judges:**
- AI correctly ranks the Laravel expert #1
- AI correctly flags the marketer as bottom
- Expanding the top candidate shows green matched skills (Laravel, PHP, MySQL...)
- Expanding the bottom candidate shows red missing skills + risk flags
- Score breakdown bars make the reasoning visually obvious

**Click path:**
1. Admin Dashboard → Quản lý Jobs → Find "Backend Developer (Laravel/PHP)"
2. Click "Ứng viên" → Click "🤖 AI Shortlist" (purple gradient button)
3. Top candidate is auto-expanded → show matched skills, score breakdown
4. Scroll to last candidate → click to expand → show missing skills, low score
5. Point out: "AI giải thích tại sao — không phải black box"

### Path B: Cross-Domain Contrast — "Senior Frontend Developer"

| Candidate | Expected | Why |
|-----------|----------|-----|
| Nguyễn Minh Anh (seeded) | 🟡 Medium-High | React, TypeScript, Tailwind — good but may lack seniority |
| Eval - Hoàng Fullstack | 🟡 Medium fit | React/TypeScript overlap but MERN focus |
| Eval - Vũ Frontend Junior | 🟡 Medium fit | React match but only 1 year, no TypeScript |
| Eval - Phạm Backend Senior | 🔴 Low fit | Backend stack, no React/Next.js |

**When to use:** If judges ask "what if the ranking is less clear-cut?"

---

## 🎯 Demo Objectives

1. Show **AI automatically analyzes CVs and matches to Korean-company JDs**
2. Show the **recruiter shortlist UI** ranks and explains fit clearly
3. Show **AI is better than keyword matching** (with eval numbers)
4. Keep it under **8 minutes** total demo time

---

## 📋 Pre-Demo Checklist

### Environment Setup

- [ ] PostgreSQL running with `pgvector` extension enabled
- [ ] Laravel backend running (`php artisan serve` on port 8000, or Docker)
- [ ] AI service running (`ai-service` on port 8001)
- [ ] `.env` has valid `OPENAI_API_KEY` and `DATABASE_URL`
- [ ] Seed data loaded: `php artisan db:seed`
- [ ] Eval dataset loaded: `php artisan db:seed --class=EvalDatasetSeeder`
- [ ] **Pre-warmed:** `php artisan eval:shortlist --seed` completed (persists AI results)
- [ ] Browser logged in as admin/recruiter user
- [ ] Browser tab open on "Backend Developer (Laravel/PHP)" → Ứng viên page

### Content Readiness

- [ ] Know the recommended demo path (Path A above)
- [ ] Know candidate names: Phạm Backend Senior (high), Đức Marketing (low)
- [ ] Have eval results screenshot or terminal output ready
- [ ] Rehearsed the fallback demo (see below)
- [ ] Confirmed AI shortlist page loads instantly (results already persisted)

---

## 🎬 Demo Script (8 minutes)

### Part 1: The Problem (1 min)

> "Recruiter nhận 50-100 CV mỗi vị trí. Đọc hết mất 2-3 ngày.
> Hiện tại hoặc đọc manual, hoặc dùng keyword filter rất thô.
> Smart CV Matcher giải quyết bằng AI pipeline thông minh hơn."

### Part 2: The Pipeline (2 min)

Show the architecture slide or draw:

```
CV + JD
  → Extractor Agent (LLM structured extraction)
  → RAG Agent (pgvector retrieval for grounding)
  → Matcher Agent (6-factor hybrid scoring)
  → Explainer Agent (citation-aware reasoning)
  → Critic Agent (confidence validation)
  → Recruiter Shortlist UI
```

Key talking points:
- "Không chỉ là wrapper quanh ChatGPT — có RAG, có deterministic scoring, có multi-agent"
- "Score công thức rõ ràng: Required skills 40%, Preferred 15%, Experience 15%, Seniority 10%, Domain 10%, Confidence 10%"
- "Persisted results — chạy 1 lần, xem lại nhiều lần"

### Part 3: Live Demo — Recruiter Flow (3 min)

> **Use Path A: "Backend Developer (Laravel/PHP)"**
> Note: The applications page now shows only the AI Shortlist button — no distractions.

1. **Dashboard** → scroll to job list → click "Xem ứng viên" for Backend Developer
2. **Job Applications page** → Click "🤖 AI Shortlist" (the only action button)
3. **AI Shortlist page** → Top candidate is auto-expanded with purple highlight
3. **Show #1 candidate (Phạm Backend Senior):**
   - ✅ Matched skills: Laravel, PHP, MySQL, Redis, Docker (green tags)
   - 📊 Score breakdown: required_skill_coverage bar is high
   - "AI giải thích rõ tại sao ứng viên này phù hợp"
4. **Scroll to bottom → expand last candidate (Đức Marketing):**
   - ❌ Missing: Laravel, PHP, MySQL — all red tags
   - ⚠️ Risk flags visible
   - "AI cũng nói rõ tại sao KHÔNG phù hợp"
5. **Click "Tính lại AI"** on any candidate → Show refresh with success banner
6. **Point out badges:** "Mới tính" (fresh), "Đã lưu" (persisted), stale indicator

Key talking points:
- "Recruiter thấy ngay ai top, ai thiếu gì, risk ở đâu"
- "Mỗi kết quả có giải thích — không phải black box"
- "Refresh bất kỳ lúc nào, kết quả được lưu để truy vết sau"

### Part 4: Evaluation Results (2 min)

Show terminal output of `php artisan eval:shortlist`:

- "Trên eval set 12 cặp CV-JD, AI hybrid matcher có score separation X.X, so với ML baseline chỉ Y.Y"
- "AI phân biệt được high fit vs low fit tốt hơn keyword overlap"
- "Precision@3: AI đạt Z, baseline chỉ W"

**IMPORTANT**: Say "trên eval set nhỏ này" — do NOT claim production accuracy.

> If numbers aren't great: "Đây là starting point. Với data thật và tuning, sẽ cải thiện."

---

## 🔥 If Things Go Wrong — Fallback Plan

### Scenario 1: AI service is down

**What happens:** AI Shortlist page shows error rows with risk flag "AI matching failed".
**What to say:** "Hệ thống có fallback — khi AI service không khả dụng, vẫn hiển thị trạng thái lỗi rõ ràng thay vì crash. Recruiter thấy rõ ứng viên nào chưa được AI đánh giá."
**Action:** Show the existing ML scoring (Chấm CV button) as the non-AI alternative.

### Scenario 2: AI service returns slowly (>10s)

**What to say:** "Lần chạy đầu mất thời gian vì phải gọi AI cho từng ứng viên. Sau đó kết quả được lưu — lần sau load ngay."
**Action:** Show a candidate that already has persisted results (loads instantly).
**Prevention:** Run `php artisan eval:shortlist --seed` before demo to pre-warm all results.

### Scenario 3: OpenAI API key issues

**Pre-demo action:** Test with `curl http://localhost:8001/docs` to verify AI service is up.
**If it fails live:** Switch to `php artisan eval:shortlist --no-ai` (uses only persisted results).
**Shortlist page:** Will still work if results were pre-warmed.

### Scenario 4: Database not seeded

**Action:** `php artisan db:seed && php artisan db:seed --class=EvalDatasetSeeder`
**Time needed:** ~10 seconds.

### Scenario 5: Judge asks "What about bias?"

**Answer:** "Chúng tôi có PII masking trước khi gửi LLM — email, phone, URL được redact. Scoring formula là deterministic (không phụ thuộc tên/giới tính). Đây là area tiếp tục improve."

### Scenario 6: Shortlist page shows all errors

**Why:** AI service was never reached, no results persisted.
**Fix:** Run `php artisan eval:shortlist --seed` in terminal to populate results, then reload page.

---

## 📊 Eval Report Template

After running `php artisan eval:shortlist`, copy the key numbers here:

### Last Run: [DATE]

| Metric | ML Pipeline | Keyword Overlap | AI Hybrid |
|--------|-------------|-----------------|-----------|
| Mean score (high_fit) | ___ | ___ | ___ |
| Mean score (low_fit) | ___ | ___ | ___ |
| Score separation | ___ | ___ | ___ |
| Precision@3 (avg) | ___ | ___ | ___ |
| Latency p50 (ms) | ___ | ___ | ___ |

**Dataset:** 12 eval pairs (4 high, 4 medium, 4 low)
**Note:** Small eval set — demo-quality, not production claims.

---

## 🗣️ Q&A Prep

### "Sao không dùng fine-tuned model?"
"Phase hiện tại focus vào pipeline architecture — RAG + multi-agent + deterministic scoring. Fine-tuning là roadmap item khi có data đủ lớn."

### "Precision@3 chỉ có X — thấp quá?"
"Đúng, eval set nhỏ (12 pairs). Nhưng quan trọng là AI hybrid có separation cao hơn baselines. Với real data sẽ validate lại."

### "Chi phí API?"
"Mỗi match call ~$0.002-0.005 (GPT-4o-mini). Kết quả persist nên chỉ call 1 lần/ứng viên. Fallback ML pipeline miễn phí."

### "So với ChatGPT zero-shot?"
"ChatGPT zero-shot = Level 1 wrapper. Chúng tôi có RAG grounding, deterministic scoring formula, multi-agent validation, persistent results, và recruiter-facing explainability."

### "Score dựa trên gì?"
"6 yếu tố: Required skills 40%, Preferred skills 15%, Experience fit 15%, Seniority fit 10%, Domain relevance 10%, Confidence adjustment 10%. Công thức deterministic, không random."

---

## ⏱️ Quick Command Reference

```bash
# Seed everything
php artisan db:seed
php artisan db:seed --class=EvalDatasetSeeder

# Pre-warm AI results (IMPORTANT: run before demo!)
php artisan eval:shortlist --seed

# Run evaluation only (data already seeded + warmed)
php artisan eval:shortlist
php artisan eval:shortlist --no-ai          # Skip AI calls, use persisted results

# Start services (if not using Docker)
php artisan serve                           # Laravel backend (port 8000)
cd ai-service && uvicorn app.main:app --port 8001  # AI service (port 8001)

# Verify services
curl http://localhost:8001/docs             # Should show FastAPI docs page
curl http://localhost:8000                  # Should show Laravel app

# Demo page URL (after login)
# http://localhost:8000/admin/jobs → find "Backend Developer" → Ứng viên → 🤖 AI Shortlist
```
