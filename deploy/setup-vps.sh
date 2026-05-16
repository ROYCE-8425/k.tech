#!/bin/bash
# =============================================================================
# Smart CV Matcher — FULL AUTO DEPLOY (Ubuntu 22.04)
# =============================================================================
# Run this on the VPS after copying the project files.
#
# QUICK START:
#   1. On your Windows machine, open PowerShell:
#      scp -r D:\web\cpanel_public_html\* root@160.191.237.64:/var/www/smartcv/
#
#   2. SSH into VPS:
#      ssh root@160.191.237.64
#
#   3. Run this script:
#      cd /var/www/smartcv && bash deploy/setup-vps.sh
# =============================================================================

set -euo pipefail

# ─── CONFIG ──────────────────────────────────────────────────────────────────
APP_DIR="/var/www/smartcv"
SERVER_IP="160.191.237.64"
DB_NAME="recruitment_app"
DB_USER="cvmatcher"
DB_PASS="SmartCV_$(date +%s | md5sum | head -c 8)"
PHP_VER="8.2"
PY_VER="3.11"
NODE_VER="18"

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
    err "Must run as root"
    exit 1
fi

if [ ! -f "${APP_DIR}/docker-compose.yml" ]; then
    err "Project files not found at ${APP_DIR}/"
    echo ""
    echo "  Copy project first (from your Windows machine):"
    echo "  scp -r D:\\web\\cpanel_public_html\\* root@${SERVER_IP}:${APP_DIR}/"
    echo ""
    echo "  Or if already somewhere else on the VPS:"
    echo "  cp -r /path/to/project/* ${APP_DIR}/"
    echo ""
    mkdir -p ${APP_DIR}
    exit 1
fi

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   Smart CV Matcher — Full VPS Deployment        ║"
echo "║   Target: ${SERVER_IP}                    ║"
echo "║   Stack: Nginx + PHP + Python + PostgreSQL      ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# ─── 1. SYSTEM UPDATE ────────────────────────────────────────────────────────
step "1/10 — System update"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq \
    curl wget git unzip software-properties-common \
    ca-certificates gnupg lsb-release ufw acl
log "System packages updated"

# ─── 2. NGINX ────────────────────────────────────────────────────────────────
step "2/10 — Nginx"
apt-get install -y -qq nginx
systemctl enable nginx
log "Nginx installed"

# ─── 3. PHP 8.2 ──────────────────────────────────────────────────────────────
step "3/10 — PHP ${PHP_VER}"
add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common \
    php${PHP_VER}-pgsql php${PHP_VER}-sqlite3 php${PHP_VER}-mbstring \
    php${PHP_VER}-xml php${PHP_VER}-curl php${PHP_VER}-zip \
    php${PHP_VER}-gd php${PHP_VER}-bcmath php${PHP_VER}-intl \
    php${PHP_VER}-readline php${PHP_VER}-tokenizer php${PHP_VER}-dom \
    php${PHP_VER}-fileinfo
systemctl enable php${PHP_VER}-fpm
systemctl start php${PHP_VER}-fpm
log "PHP ${PHP_VER} installed"

# ─── 4. COMPOSER ─────────────────────────────────────────────────────────────
step "4/10 — Composer"
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --quiet
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi
log "Composer $(composer --version 2>/dev/null | head -1 || echo 'installed')"

# ─── 5. NODE.JS ──────────────────────────────────────────────────────────────
step "5/10 — Node.js ${NODE_VER}"
if ! command -v node &>/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_${NODE_VER}.x | bash - >/dev/null 2>&1
    apt-get install -y -qq nodejs
fi
log "Node $(node -v) / npm $(npm -v)"

# ─── 6. PYTHON ───────────────────────────────────────────────────────────────
step "6/10 — Python ${PY_VER}"
add-apt-repository -y ppa:deadsnakes/ppa >/dev/null 2>&1
apt-get update -qq
apt-get install -y -qq python${PY_VER} python${PY_VER}-venv python${PY_VER}-dev python3-pip
log "Python $(python${PY_VER} --version)"

# ─── 7. POSTGRESQL + PGVECTOR ────────────────────────────────────────────────
step "7/10 — PostgreSQL + pgvector"
apt-get install -y -qq postgresql postgresql-contrib

# Determine PG version
PG_MAJOR=$(pg_config --version 2>/dev/null | grep -oP '\d+' | head -1 || echo "14")

# Try installing pgvector package
apt-get install -y -qq postgresql-${PG_MAJOR}-pgvector 2>/dev/null || {
    warn "pgvector package not available, building from source..."
    apt-get install -y -qq postgresql-server-dev-${PG_MAJOR} build-essential git
    cd /tmp
    rm -rf pgvector
    git clone --branch v0.7.0 https://github.com/pgvector/pgvector.git
    cd pgvector && make && make install
    cd ${APP_DIR}
    log "pgvector built from source"
}

systemctl enable postgresql
systemctl start postgresql

# Create DB user and database
sudo -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';" >/dev/null
sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};" >/dev/null
sudo -u postgres psql -d "${DB_NAME}" -c "CREATE EXTENSION IF NOT EXISTS vector;" >/dev/null 2>&1 || \
    warn "pgvector extension not available"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};" >/dev/null

# Fix peer auth → md5 for local connections
PG_HBA="/etc/postgresql/${PG_MAJOR}/main/pg_hba.conf"
if [ -f "$PG_HBA" ]; then
    sed -i 's/^local\s\+all\s\+all\s\+peer/local   all             all                                     md5/' "$PG_HBA"
    systemctl restart postgresql
fi

log "PostgreSQL ready (db=${DB_NAME}, user=${DB_USER})"

# ─── 8. LARAVEL SETUP ────────────────────────────────────────────────────────
step "8/10 — Laravel backend"
cd ${APP_DIR}/backend

# Install dependencies
log "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>&1 || \
    composer install --no-dev --no-interaction 2>&1 | tail -5

# Generate app key
APP_KEY=$(php artisan key:generate --show 2>/dev/null)
log "App key generated"

# Write production .env
cat > ${APP_DIR}/backend/.env << ENVEOF
APP_NAME="Smart AI Recruitment System"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=http://${SERVER_IP}
APP_TIMEZONE=Asia/Ho_Chi_Minh

DEMO_MODE=true

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

AI_ORCHESTRATOR_BASE_URL=http://127.0.0.1:8001
AI_ORCHESTRATOR_TIMEOUT_SECONDS=25

QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
LOG_CHANNEL=stack
LOG_LEVEL=info

IT_SOLO_LEVELING_SIGNATURE=IT_SOLO_LEVELING_PROD_$(openssl rand -hex 8)
ENVEOF

# Migrate + seed
log "Running migrations..."
php artisan migrate --force 2>&1 | tail -5 || warn "Migration had warnings"
log "Seeding demo data..."
php artisan db:seed --class=DemoSeeder --force 2>&1 | tail -3 || warn "Seeder had warnings"

# Optimize
php artisan optimize 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Build frontend assets
if [ -f "${APP_DIR}/backend/package.json" ]; then
    log "Building frontend assets..."
    cd ${APP_DIR}/backend
    npm install 2>&1 | tail -3
    npm run build 2>&1 | tail -5 || warn "Frontend build issue (non-critical)"
fi

# Permissions
chown -R www-data:www-data ${APP_DIR}/backend/storage ${APP_DIR}/backend/bootstrap/cache
chmod -R 775 ${APP_DIR}/backend/storage ${APP_DIR}/backend/bootstrap/cache
# Ensure SQLite DB file is writable if fallback
touch ${APP_DIR}/backend/database/database.sqlite 2>/dev/null || true
chown www-data:www-data ${APP_DIR}/backend/database/database.sqlite 2>/dev/null || true

log "Laravel configured"

# ─── 9. AI SERVICE SETUP ─────────────────────────────────────────────────────
step "9/10 — AI Service (FastAPI)"
cd ${APP_DIR}/ai-service

# Create venv and install deps
python${PY_VER} -m venv venv
source venv/bin/activate
pip install --upgrade pip -q 2>&1 | tail -1
pip install -r requirements.txt -q 2>&1 | tail -5 || {
    warn "Some pip packages failed, trying without extras..."
    pip install fastapi uvicorn pydantic httpx openai asyncpg 2>&1 | tail -3
}

# Write AI service .env (API keys placeholder — user fills in)
cat > ${APP_DIR}/ai-service/.env << AIENVEOF
APP_ENV=production
LOG_LEVEL=info

# Database for pgvector retrieval
DATABASE_URL=postgresql://${DB_USER}:${DB_PASS}@127.0.0.1:5432/${DB_NAME}

# LLM Provider (xai / openai / gemini / auto-detect)
LLM_PROVIDER=xai

# xAI / Grok
XAI_API_KEY=
XAI_EXTRACTION_MODEL=grok-3-mini
XAI_BASE_URL=https://api.x.ai/v1

# OpenAI
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
EMBEDDING_MODEL=text-embedding-3-small

# Google Gemini
GEMINI_API_KEY=

# Feedback reranking
FEEDBACK_RERANK_ENABLED=true
AIENVEOF

deactivate

# Systemd service for AI
cat > /etc/systemd/system/smartcv-ai.service << SVCEOF
[Unit]
Description=Smart CV Matcher AI Service
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=${APP_DIR}/ai-service
Environment="PATH=${APP_DIR}/ai-service/venv/bin:/usr/local/bin:/usr/bin"
EnvironmentFile=${APP_DIR}/ai-service/.env
ExecStart=${APP_DIR}/ai-service/venv/bin/uvicorn app.main:app --host 127.0.0.1 --port 8001 --workers 1
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
SVCEOF

# Fix ownership for www-data
chown -R www-data:www-data ${APP_DIR}/ai-service

systemctl daemon-reload
systemctl enable smartcv-ai
systemctl restart smartcv-ai
sleep 2

# Check if AI service started
if systemctl is-active --quiet smartcv-ai; then
    log "AI service running on port 8001"
else
    warn "AI service failed to start (check: journalctl -u smartcv-ai -n 20)"
fi

# ─── 10. NGINX CONFIG ────────────────────────────────────────────────────────
step "10/10 — Nginx configuration"

cat > /etc/nginx/sites-available/smartcv << 'NGINXEOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;

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

# Test and restart
nginx -t 2>&1 && systemctl restart nginx || {
    err "Nginx config test failed!"
    nginx -t 2>&1
}

log "Nginx configured"

# ─── FIREWALL ────────────────────────────────────────────────────────────────
step "Firewall"
ufw allow ssh >/dev/null
ufw allow 80 >/dev/null
ufw allow 443 >/dev/null
echo "y" | ufw enable >/dev/null 2>&1 || true
log "Firewall configured (22, 80, 443)"

# ─── SWAP (for 2GB RAM VPS) ──────────────────────────────────────────────────
step "Swap space"
if [ ! -f /swapfile ]; then
    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    log "1GB swap created"
else
    log "Swap already exists"
fi

# ─── FINAL STATUS ────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║   ✅ DEPLOYMENT COMPLETE                        ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "  🌐 Public URL:     http://${SERVER_IP}"
echo "  🌐 Demo Landing:   http://${SERVER_IP}/demo"
echo "  🤖 AI Service:     http://127.0.0.1:8001 (internal)"
echo "  🗄  Database:       PostgreSQL @ 127.0.0.1:5432/${DB_NAME}"
echo ""
echo "  📁 App Directory:  ${APP_DIR}"
echo "  📁 Laravel:        ${APP_DIR}/backend"
echo "  📁 AI Service:     ${APP_DIR}/ai-service"
echo ""
echo "  ─── Credentials (SAVE THESE) ───"
echo "  DB Password:       ${DB_PASS}"
echo "  App Key:           ${APP_KEY}"
echo ""
echo "  ─── Service Management ───"
echo "  systemctl status smartcv-ai        # AI service status"
echo "  systemctl restart smartcv-ai       # Restart AI"
echo "  systemctl restart php${PHP_VER}-fpm     # Restart PHP"
echo "  systemctl restart nginx            # Restart Nginx"
echo "  journalctl -u smartcv-ai -f        # AI logs"
echo ""
echo "  ─── IMPORTANT: Add API keys ───"
echo "  nano ${APP_DIR}/ai-service/.env"
echo "  # Fill in XAI_API_KEY, OPENAI_API_KEY, GEMINI_API_KEY"
echo "  systemctl restart smartcv-ai"
echo ""

# Save deploy info
cat > /root/.smartcv-deploy-info << EOF
DEPLOY_DATE=$(date -Iseconds)
SERVER_IP=${SERVER_IP}
APP_DIR=${APP_DIR}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
APP_KEY=${APP_KEY}
EOF
chmod 600 /root/.smartcv-deploy-info
