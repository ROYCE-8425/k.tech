# AI CV Scoring Note

## Status
**Implemented (Phase 20)**

Chuc nang nay duoc de xuat de mo rong he thong Smart CV Matcher theo huong:
- cham CV minh bach hon
- giai thich duoc vi sao diem tang/giam
- phan biet ro giua keyword matching co ban va hybrid AI scoring nang cao

---

## Muc tieu
Xay dung mot co che **AI CV Scoring** de cham muc do phu hop giua CV va Job Description theo nhieu lop ly luan, thay vi chi dua tren:
- keyword matching
- hoac 1 lan goi LLM don le

Chuc nang nay phuc vu:
- recruiter review
- explainability
- score breakdown
- slide/demo/bai bao cao ky thuat

---

## Nguyen tac cham diem

### 1. LLM khong cham diem cuoi
LLM chi nen duoc dung de:
- doc CV/JD phi cau truc
- trich xuat du lieu
- chuan hoa skill / seniority / domain
- ho tro explanation

**Fit score cuoi phai do code tinh theo cong thuc ro rang.**

### 2. Cham theo hybrid scoring
He thong nen cham theo nhieu thanh phan:
- required skill coverage
- preferred skill coverage
- experience fit
- seniority fit
- domain relevance
- confidence adjustment

Neu co:
- related skill reasoning
- graph reasoning
- recruiter feedback signal

thi cac thanh phan nay phai la **bounded adjustment**, khong duoc ghi de score tro nen mo ho.

### 3. Giai thich duoc
Moi ket qua cham diem can co:
- diem tong
- rank label
- confidence
- matched skills
- missing skills
- related matches
- risk flags
- score breakdown

---

## Quy trinh cham CV bang AI

### Step 1. Extract candidate profile
Trich xuat tu CV / profile:
- skills
- years of experience
- seniority
- projects
- education
- domain keywords

### Step 2. Extract job profile
Lay tu du lieu structured cua job neu co:
- required_skills
- preferred_skills
- seniority
- min_experience_years
- max_experience_years
- scoring_config
- ai_recruiter_notes

Neu du lieu job chua du:
- fallback sang extraction / heuristic

### Step 3. Normalize
Chuan hoa:
- skill aliases
- seniority aliases
- domain keywords

Vi du:
- js -> JavaScript
- postgres -> PostgreSQL
- node -> Node.js

### Step 4. Match by layers
Cham theo cac lop:
- exact match
- synonym match
- related skill match
- graph-lite one-hop
- graph-lite two-hop neu co
- experience fit
- seniority fit
- domain relevance

### Step 5. Compute deterministic score
Tinh `fit_score` bang cong thuc co trong so.

Vi du:
- required skills: 40%
- preferred skills: 15%
- experience: 15%
- seniority: 10%
- domain relevance: 10%
- confidence: 10%

### Step 6. Critic / validation
Gan:
- risk flags
- low confidence warnings
- missing required skill warnings
- recruiter review recommendations

### Step 7. Explanation
Tra ve:
- score breakdown
- ly do chinh
- diem manh
- diem thieu
- goi y review

---

## Gia tri cua chuc nang

Neu build dung, chuc nang nay se giup:
- recruiter tin hon vao ket qua
- candidate nhan duoc advisory ro rang hon
- he thong giai thich duoc vi sao score thay doi
- phan biet du an voi keyword matching thong thuong

---

## Khac biet voi keyword matching

### Keyword matching co ban
- chi dem skill trung lap
- de bo sot related skill
- khong giai thich sau
- khong audit tot

### AI CV Scoring nang cao
- dung extraction co cau truc
- chuan hoa ky nang
- ho tro related-skill reasoning
- ket hop experience / seniority / domain
- deterministic score
- explainable output

---

## Huong implement de xuat (Đã hoàn thành)

Chức năng này đã được triển khai thông qua:
1. Sử dụng Multi-Agent Council (5 tác nhân) hoạt động như một **lớp tư vấn (Advisory Layer)** thông qua `GptScoringService`.
2. **Canonical `fit_score` tuyệt đối không bị thay đổi bởi LLM**, mà hoàn toàn đến từ thuật toán deterministic của pipeline Python backend.
3. Thêm recruiter-facing score breakdown view qua `ai-xray.blade.php`, nơi hiển thị ý kiến của Council tách biệt rõ ràng với điểm số gốc.
4. Bổ sung `ai_trends` tĩnh để cung cấp context (trend lenses) được kiểm soát, không bịa đặt xu hướng internet.
5. Không để LLM tự chấm điểm tổng một cách cảm tính mà dùng nó để phân tích, tổng hợp lý lẽ dựa trên canonical score đã có.

---

## Ghi chu bao cao / slide
Co the mo ta ngan gon nhu sau:

> He thong su dung hybrid AI scoring de cham muc do phu hop giua CV va JD.  
> LLM duoc dung de trich xuat va hieu du lieu phi cau truc, trong khi diem so cuoi cung duoc tinh bang cong thuc deterministic ket hop:
> - skill match,
> - related skill reasoning,
> - experience,
> - seniority,
> - domain relevance,
> - va confidence.  
> Cach tiep can nay giup he thong minh bach, giai thich duoc, va vuot qua keyword matching thong thuong.
