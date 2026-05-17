#!/bin/bash
# =============================================================================
# Smart CV Matcher — SSL Setup (Let's Encrypt / Certbot)
# =============================================================================
# Domain: likefood.io.vn
# VPS IP: 160.191.237.64
#
# PREREQUISITES:
#   1. Domain A record must point to 160.191.237.64
#   2. setup-vps.sh must have been run first (Nginx already installed)
#   3. Port 80 and 443 must be open in firewall
#
# USAGE:
#   ssh root@160.191.237.64
#   cd /var/www/smartcv && bash deploy/setup-ssl.sh
# =============================================================================

set -euo pipefail

# ─── CONFIG ──────────────────────────────────────────────────────────────────
DOMAIN="likefood.io.vn"
APP_DIR="/var/www/smartcv"
EMAIL="admin@likefood.io.vn"   # For Let's Encrypt notifications

# Color helpers
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; }
step() { echo -e "\n${GREEN}━━━ $1 ━━━${NC}"; }

# ─── PRE-CHECK ───────────────────────────────────────────────────────────────
if [ "$(id -u)" -ne 0 ]; then
    err "Phải chạy với quyền root"
    exit 1
fi

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   🔒 SSL Setup — Let's Encrypt (Certbot)       ║"
echo "║   Domain: ${DOMAIN}                    ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# ─── 1. CHECK DNS ────────────────────────────────────────────────────────────
step "1/5 — Kiểm tra DNS"
RESOLVED_IP=$(dig +short ${DOMAIN} 2>/dev/null | head -1 || echo "")
if [ -z "$RESOLVED_IP" ]; then
    warn "Không resolve được DNS cho ${DOMAIN}"
    warn "Hãy chắc chắn A record đã trỏ về VPS IP"
    read -p "Tiếp tục anyway? (y/N): " CONTINUE
    [ "$CONTINUE" != "y" ] && exit 1
else
    log "DNS OK: ${DOMAIN} → ${RESOLVED_IP}"
fi

# ─── 2. INSTALL CERTBOT ─────────────────────────────────────────────────────
step "2/5 — Cài đặt Certbot"
apt-get update -qq
apt-get install -y -qq certbot python3-certbot-nginx
log "Certbot đã cài xong"

# ─── 3. UPDATE NGINX CONFIG FOR DOMAIN ──────────────────────────────────────
step "3/5 — Cập nhật Nginx config"

# Backup current config
cp /etc/nginx/sites-available/smartcv /etc/nginx/sites-available/smartcv.bak.$(date +%s) 2>/dev/null || true

cat > /etc/nginx/sites-available/smartcv << 'NGINXEOF'
server {
    listen 80;
    listen [::]:80;
    server_name likefood.io.vn www.likefood.io.vn;

    root /var/www/smartcv/backend/public;
    index index.php index.html;

    client_max_body_size 20M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Laravel — all routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM handler
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 60;
    }

    # Block dotfiles
    location ~ /\.(?!well-known) {
        deny all;
    }

    # Static asset cache
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
}
NGINXEOF

# Enable site
ln -sf /etc/nginx/sites-available/smartcv /etc/nginx/sites-enabled/smartcv
rm -f /etc/nginx/sites-enabled/default

# Test nginx config
nginx -t 2>&1 && systemctl reload nginx || {
    err "Nginx config test failed!"
    nginx -t 2>&1
    exit 1
}
log "Nginx config đã cập nhật với domain ${DOMAIN}"

# ─── 4. OBTAIN SSL CERTIFICATE ──────────────────────────────────────────────
step "4/5 — Lấy chứng chỉ SSL từ Let's Encrypt"

certbot --nginx \
    -d ${DOMAIN} \
    -d www.${DOMAIN} \
    --non-interactive \
    --agree-tos \
    --email ${EMAIL} \
    --redirect \
    --keep-until-expiring \
    --staple-ocsp

if [ $? -eq 0 ]; then
    log "✅ SSL certificate đã cài thành công!"
else
    err "Certbot thất bại. Thử lại chỉ với domain chính (không www)..."
    certbot --nginx \
        -d ${DOMAIN} \
        --non-interactive \
        --agree-tos \
        --email ${EMAIL} \
        --redirect \
        --keep-until-expiring \
        --staple-ocsp || {
        err "Certbot vẫn thất bại!"
        echo ""
        echo "  Nguyên nhân thường gặp:"
        echo "  1. DNS chưa trỏ đúng về IP VPS"
        echo "  2. Port 80/443 bị firewall chặn"
        echo "  3. Nginx config bị lỗi"
        echo ""
        echo "  Kiểm tra: certbot certonly --nginx -d ${DOMAIN} -v"
        exit 1
    }
fi

# ─── 5. UPDATE LARAVEL .ENV ─────────────────────────────────────────────────
step "5/5 — Cập nhật Laravel .env"

# Update APP_URL to HTTPS
if [ -f "${APP_DIR}/backend/.env" ]; then
    sed -i "s|APP_URL=http://.*|APP_URL=https://${DOMAIN}|g" ${APP_DIR}/backend/.env
    log "APP_URL đã đổi thành https://${DOMAIN}"

    # Clear Laravel cache
    cd ${APP_DIR}/backend
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    log "Laravel cache đã clear"
fi

# ─── 6. VERIFY AUTO-RENEWAL ─────────────────────────────────────────────────
step "Kiểm tra auto-renewal"
certbot renew --dry-run 2>&1 | tail -3
log "Auto-renewal OK (tự gia hạn mỗi 60 ngày)"

# ─── FIREWALL ────────────────────────────────────────────────────────────────
ufw allow 443 >/dev/null 2>&1 || true
log "Port 443 đã mở"

# ─── DONE ────────────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   ✅ SSL ĐÃ CÀI THÀNH CÔNG!                   ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "  🔒 HTTPS URL:    https://${DOMAIN}"
echo "  🔒 HTTPS (www):  https://www.${DOMAIN}"
echo "  🔄 Auto-renew:   Có (certbot tự gia hạn)"
echo ""
echo "  📋 Certificate info:"
certbot certificates 2>/dev/null | grep -A4 "${DOMAIN}" || echo "  (run: certbot certificates)"
echo ""
echo "  ─── Quản lý SSL ───"
echo "  certbot certificates              # Xem certificate"
echo "  certbot renew                     # Gia hạn thủ công"
echo "  certbot renew --dry-run           # Test gia hạn"
echo "  nginx -t && systemctl reload nginx # Reload Nginx"
echo ""
