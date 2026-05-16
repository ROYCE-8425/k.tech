# SOURCE CODE - HỆ THỐNG TUYỂN DỤNG IT THÔNG MINH
## Ứng dụng Machine Learning để Chấm điểm CV Tự động

---

## 📁 CẤU TRÚC THƯ MỤC

```
SOURCE_CODE_SUBMISSION/
├── 1_ML_Services/          # Các service Machine Learning
├── 2_AHP_Algorithm/        # Thuật toán AHP (Saaty 1980)
├── 3_Controllers/          # Controllers xử lý logic
├── 4_Models/               # Models (Eloquent ORM)
├── 5_Views/                # Blade templates
├── 6_Documentation/        # Tài liệu kỹ thuật
└── README.md               # File này
```

---

## 📂 1. ML_Services - Dịch vụ Machine Learning

| File | Mô tả |
|------|-------|
| `MLFeatureExtractor.php` | Trích xuất đặc trưng từ CV (skills, experience, education) |
| `MLGroupScorer.php` | Chấm điểm theo 3 nhóm A, B, C với trọng số AHP |
| `RandomForestScorer.php` | Thuật toán Random Forest Regressor |
| `CvAutoScoringService.php` | Service chấm điểm CV tự động |
| `CvRubricScoringService.php` | Chấm điểm theo rubric/thang đo |

---

## 📂 2. AHP_Algorithm - Thuật toán Phân tích Thứ bậc

| File | Mô tả |
|------|-------|
| `AHPWeightInitializer.php` | Tính trọng số ban đầu bằng AHP (Saaty 1980) |
| `AHPWeightByJobCategory.php` | Trọng số tùy chỉnh theo loại công việc (Frontend, Backend, DevOps...) |

**Tham khảo:** Saaty, T.L. (1980). *The Analytic Hierarchy Process*. McGraw-Hill.

---

## 📂 3. Controllers - Xử lý Logic

| File | Mô tả |
|------|-------|
| `CandidateJobController.php` | Xử lý ứng tuyển, tạo CV nhanh, nộp đơn |
| `CompanyController.php` | Quản lý công ty/nhà tuyển dụng |

---

## 📂 4. Models - Mô hình Dữ liệu

| File | Mô tả |
|------|-------|
| `User.php` | Người dùng (candidate, employer, admin) |
| `Candidate.php` | Thông tin ứng viên |
| `Company.php` | Thông tin công ty |
| `Job.php` | Tin tuyển dụng |
| `Application.php` | Đơn ứng tuyển + điểm CV |
| `CvScoringProfile.php` | Cấu hình chấm điểm |
| `CvScoringOverride.php` | Ghi đè điểm thủ công |

---

## 📂 5. Views - Giao diện

| File | Mô tả |
|------|-------|
| `jobs_show.blade.php` | Trang chi tiết công việc + form ứng tuyển |
| `candidate_profile.blade.php` | Trang hồ sơ ứng viên |

---

## 📂 6. Documentation - Tài liệu

| File | Mô tả |
|------|-------|
| `ML_CV_SCORING_DOCUMENTATION.md` | Tài liệu chi tiết hệ thống ML chấm điểm |
| `AI_ALGORITHMS_DOCUMENTATION.md` | Tài liệu các thuật toán AI |
| `SECURITY_ALGORITHMS_DOCUMENTATION.md` | Tài liệu bảo mật |

---

## 🔧 CÔNG NGHỆ SỬ DỤNG

- **Backend:** PHP 8.1+, Laravel 10
- **Database:** MySQL 8.0
- **Frontend:** Blade, TailwindCSS, Alpine.js
- **ML:** Random Forest Regressor (100 trees, R² = 0.9326)
- **Algorithm:** AHP (Saaty 1980), Weighted Sum Model

---

## 📊 KẾT QUẢ ĐÁNH GIÁ

| Metric | Giá trị |
|--------|---------|
| R² Score | 0.9326 (93.26%) |
| MAE | 3.32 điểm |
| AHP Consistency Ratio | < 0.1 (tất cả nhóm) |

---

## 📚 TÀI LIỆU THAM KHẢO

1. Saaty, T.L. (1980). *The Analytic Hierarchy Process*. McGraw-Hill, New York.
2. Breiman, L. (2001). *Random Forests*. Machine Learning, 45(1), 5-32.
3. Laravel Documentation. https://laravel.com/docs

---

**Sinh viên thực hiện:** [TÊN SINH VIÊN]  
**MSSV:** [MÃ SỐ]  
**Lớp:** [LỚP]  
**Năm:** 2026
