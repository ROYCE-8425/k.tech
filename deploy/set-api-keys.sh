#!/bin/bash
# Quick script to set API keys on the VPS
# Usage: bash deploy/set-api-keys.sh

APP_DIR="/var/www/smartcv"
ENV_FILE="${APP_DIR}/ai-service/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo "Error: ${ENV_FILE} not found"
    exit 1
fi

# Set the API keys (replace with your actual keys)
sed -i "s|^XAI_API_KEY=.*|XAI_API_KEY=xai-GPlclJCfCWQalxpxQWmNXr4o3vwhXEvu4IPWhuc5FlfPqgt1w8Jojbb6Egydx3DEdD3bVwEoO9OwwqhN|" "$ENV_FILE"
sed -i "s|^OPENAI_API_KEY=.*|OPENAI_API_KEY=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|" "$ENV_FILE"
sed -i "s|^GEMINI_API_KEY=.*|GEMINI_API_KEY=AIzaSyCAH4G1jmESQQIhrOF9UTofEjzUep9NbmQ|" "$ENV_FILE"

echo "API keys updated in ${ENV_FILE}"
echo "Restarting AI service..."
systemctl restart smartcv-ai
sleep 2

if systemctl is-active --quiet smartcv-ai; then
    echo "✅ AI service restarted successfully"
else
    echo "⚠ AI service may have issues, check: journalctl -u smartcv-ai -n 20"
fi
