#!/usr/bin/env bash
# Compliance Hub — Automated Setup Script
# Run from project root: ./setup.sh

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info()  { echo -e "${BLUE}[INFO]${NC} $*"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $*"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
log_error() { echo -e "${RED}[ERROR]${NC} $*"; }

check_command() {
    if ! command -v "$1" &> /dev/null; then
        log_error "$1 not found. Please install it first."
        exit 1
    fi
}

check_version() {
    local cmd="$1"
    local min_version="$2"
    local version_flag="${3:---version}"
    local current_version
    current_version=$($cmd $version_flag 2>/dev/null | head -1 | grep -oE '[0-9]+\.[0-9]+(\.[0-9]+)?' | head -1)
    if [[ -z "$current_version" ]]; then
        log_warn "Could not determine $cmd version"
        return
    fi
    # Simple version compare (works for major.minor)
    if [[ "$(printf '%s\n' "$min_version" "$current_version" | sort -V | head -1)" != "$min_version" ]]; then
        log_warn "$cmd version $current_version < $min_version (may cause issues)"
    else
        log_ok "$cmd $current_version"
    fi
}

main() {
    echo "=========================================="
    echo "  Compliance Hub — Automated Setup"
    echo "=========================================="
    echo

    # 1. Prerequisites check
    log_info "Checking prerequisites..."
    check_command php
    check_command composer
    check_command node
    check_command npm
    check_command docker

    # Ollama is optional (needed for AI evidence analysis)
    OLLAMA_AVAILABLE=true
    if ! command -v ollama &> /dev/null; then
        log_warn "ollama not found. AI evidence analysis features will be disabled."
        log_warn "Install ollama from https://ollama.com to enable AI features."
        OLLAMA_AVAILABLE=false
    fi

    check_version php "8.2"
    check_version node "18"
    check_version docker "24"

    # 2. Environment file
    log_info "Setting up environment..."
    if [[ ! -f .env ]]; then
        cp .env.example .env
        log_ok "Created .env from .env.example"
    else
        log_warn ".env already exists, skipping"
    fi

    # 3. Laravel key
    if ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force
        log_ok "Generated APP_KEY"
    else
        log_ok "APP_KEY already set"
    fi

    # 4. Composer dependencies
    log_info "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    log_ok "Composer dependencies installed"

    # 5. Node dependencies & build
    log_info "Installing Node dependencies..."
    npm ci
    log_ok "npm dependencies installed"

    log_info "Building frontend assets..."
    npm run build
    log_ok "Frontend assets built"

    # 6. Docker services
    log_info "Starting Docker services (ClamAV + n8n)..."
    docker compose up -d
    log_ok "Docker services started"

    # Wait for services to be healthy
    log_info "Waiting for services to be ready..."
    sleep 5

    # 7. Ollama model (optional)
    if [[ "$OLLAMA_AVAILABLE" == "true" ]]; then
        log_info "Checking Ollama service..."
        if ! curl -s http://localhost:11434/api/tags > /dev/null 2>&1; then
            log_warn "Ollama not running. Starting in background..."
            ollama serve > /dev/null 2>&1 &
            sleep 3
        fi

        log_info "Pulling llava:7b model (one-time, ~4.7 GB)..."
        if ollama list | grep -q 'llava:7b'; then
            log_ok "llava:7b already present"
        else
            ollama pull llava:7b
            log_ok "llava:7b pulled successfully"
        fi
    else
        log_warn "Skipping Ollama setup (not installed)."
    fi

    # 8. Database
    log_info "Running migrations & seeders..."
    php artisan migrate --force
    log_ok "Database migrated and seeded"

    # 9. n8n workflow automation
    log_info "Setting up n8n workflows via API..."
    
    # Wait for n8n API to be ready (owner account is pre-provisioned via docker-compose env vars)
    log_info "Waiting for n8n API to be ready..."
    for i in {1..30}; do
        if curl -s -H "X-N8N-API-KEY: n8nComplianceHubSecretKey" http://localhost:5678/api/v1/workflows > /dev/null 2>&1; then
            log_ok "n8n API is ready"
            break
        fi
        sleep 2
    done
    
    # Run n8n:setup command (uses N8N_API_KEY from .env)
    log_info "Running n8n:setup command..."
    if php artisan n8n:setup; then
        log_ok "n8n workflows imported and activated successfully"
    else
        log_warn "n8n:setup failed. Check that n8n is fully started and try: php artisan n8n:setup"
    fi
    echo

    # 10. Final instructions
    echo "=========================================="
    log_ok "Setup complete!"
    echo "=========================================="
    echo
    echo "Next steps (run in separate terminals):"
    echo
    echo "  Terminal 1 - Web server:"
    echo "    php artisan serve --port=8000"
    echo
    echo "  Terminal 2 - Queue worker (REQUIRED for evidence analysis):"
    echo "    php artisan queue:work"
    echo
    echo "  Terminal 3 (optional) - Vite dev server:"
    echo "    npm run dev"
    echo
    echo "Then visit: http://localhost:8000"
    echo
    echo "Default users (password: password):"
    echo "  superadmin@example.com  (Super Admin)"
    echo "  admin@example.com       (Admin)"
    echo "  auditor@example.com     (Auditor)"
    echo "  customer@example.com    (Customer)"
    echo
}

main "$@"