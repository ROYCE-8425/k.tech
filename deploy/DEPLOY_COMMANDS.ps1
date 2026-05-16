# ================================================================
# SMART CV MATCHER — FULL DEPLOYMENT (Git + VPS)
# ================================================================
# Chạy từng block lệnh theo thứ tự trong PowerShell
# VPS: Ubuntu 22.04 @ 160.191.237.64
# Git: https://github.com/ROYCE-8425/k.tech.git
# ================================================================

# ════════════════════════════════════════════════════════════════
# PHẦN 1: PUSH CODE LÊN GITHUB (chạy trên Windows)
# ════════════════════════════════════════════════════════════════

cd D:\web\cpanel_public_html
git remote add origin https://github.com/ROYCE-8425/k.tech.git
git add -A
git commit -m "Full project: Phase 1-15 + VPS deployment scripts"
git branch -M main
git push -u origin main

# Nếu lỗi "remote origin already exists":
#   git remote set-url origin https://github.com/ROYCE-8425/k.tech.git
#   git push -u origin main --force


# ════════════════════════════════════════════════════════════════
# PHẦN 2: SSH VÀO VPS VÀ DEPLOY (chạy trên Windows)
# ════════════════════════════════════════════════════════════════

# Bước 1: SSH vào VPS
ssh root@160.191.237.64
# Nhập mật khẩu VPS khi được hỏi


# ════════════════════════════════════════════════════════════════
# PHẦN 3: CÁC LỆNH CHẠY TRÊN VPS (sau khi SSH vào)
# ════════════════════════════════════════════════════════════════

# Bước 2: Cài git và clone project
apt-get update && apt-get install -y git
git clone https://github.com/ROYCE-8425/k.tech.git /var/www/smartcv

# Bước 3: Chạy script deploy tự động (cài TẤT CẢ: Nginx, PHP, Python, PostgreSQL...)
cd /var/www/smartcv
bash deploy/setup-vps.sh

# Bước 4: Set API keys cho AI service
bash deploy/set-api-keys.sh

# Bước 5: Kiểm tra tất cả services
systemctl status nginx
systemctl status php8.2-fpm
systemctl status smartcv-ai
systemctl status postgresql

# Bước 6: Test nhanh
curl -s http://localhost/demo | head -5
curl -s http://localhost:8001/api/v1/health


# ════════════════════════════════════════════════════════════════
# PHẦN 4: MỞ TRÌNH DUYỆT TEST
# ════════════════════════════════════════════════════════════════
# http://160.191.237.64          — Trang chủ
# http://160.191.237.64/demo     — Demo landing
#
# Tài khoản demo:
#   Candidate: demo-candidate@smartcv.demo / demo1234
#   Recruiter: demo-recruiter@smartcv.demo / demo1234


# ════════════════════════════════════════════════════════════════
# KHI GẶP LỖI
# ════════════════════════════════════════════════════════════════

# Xem log Laravel:
# tail -f /var/www/smartcv/backend/storage/logs/laravel.log

# Xem log AI service:
# journalctl -u smartcv-ai -f

# Xem log Nginx:
# tail -f /var/log/nginx/error.log

# Restart tất cả:
# systemctl restart nginx php8.2-fpm smartcv-ai postgresql

# Chạy lại migration:
# cd /var/www/smartcv/backend && php artisan migrate --force

# Clear cache:
# cd /var/www/smartcv/backend && php artisan optimize:clear
