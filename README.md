# Compliance Hub

**Human-in-the-Loop (HITL) GRC Platform** — Automates cybersecurity compliance evidence assessment using locally-hosted AI. Evidence is uploaded, scanned for malware, analysed by a vision-language model (llava:7b), and reviewed by a human auditor who makes the final compliance determination.

Built as an MSc project artefact (University of Dhaka, Dept. of CSE).

---

## Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12, PHP ≥ 8.2, Sanctum |
| Database | SQLite (`database/database.sqlite`) |
| Queue | Database driver |
| Frontend | Vue 3 + ApexCharts, Alpine.js, Tailwind CSS 4, Vite |
| Local AI | Ollama (`llava:7b` on `:11434`) |
| Orchestration | n8n (Docker, `:5678`) |
| Antivirus | ClamAV REST (Docker, `:9000`) |
| Dev Mail | Mailpit (optional, `:8025`/`:1025`) |

**Core principle:** Evidence never leaves the deployment boundary. No external AI APIs.

---

## Quick Start

### Prerequisites (install once)

| Tool | Minimum Version | Install |
|------|-----------------|---------|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer --version` |
| Node.js | 18+ | `node -v` |
| npm | 9+ | `npm -v` |
| Docker + Compose | 24+ | `docker --version && docker compose version` |
| Ollama | Latest | `ollama --version` |

### Automated Setup (Recommended)

```bash
git clone <your-repo-url>
cd compliance-hub-laravel
chmod +x setup.sh
./setup.sh
```

The script installs dependencies, starts Docker services, pulls the AI model, runs migrations, seeds the database, **and automatically imports n8n workflows**. No manual n8n setup required — the owner account is pre-provisioned via Docker environment variables.

### Manual Setup

```bash
# 1. Clone & configure
git clone <your-repo-url>
cd compliance-hub-laravel
cp .env.example .env
php artisan key:generate

# 2. PHP dependencies
composer install

# 3. Frontend assets
npm install && npm run build

# 4. External services (Docker)
docker compose up -d
# Starts: clamav_service (:9000), n8n_service (:5678)

# 5. Local AI model (one-time, ~4.7 GB)
ollama serve &
ollama pull llava:7b

# 6. Database & seeders
php artisan migrate:fresh --seed

# 7. Import n8n workflows (fully automated)
# Owner account is pre-provisioned via docker-compose env vars
# API key is: n8nComplianceHubSecretKey
# Just run: php artisan n8n:setup

# 8. Run the app (3 terminals)
# Terminal 1: Web server
php artisan serve --port=8000

# Terminal 2: Queue worker (required for evidence analysis)
php artisan queue:work

# Terminal 3 (optional): Vite dev server for hot reload
npm run dev
```

Visit **http://localhost:8000**

---

## Default Users (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@example.com | password |
| Admin | admin@example.com | password |
| Auditor | auditor@example.com | password |
| Customer | customer@example.com | password |

---

## Key Features

- **Project Portfolio** — Multi-framework compliance projects (PCI DSS v4.0.1, ISO 27001, Bangladesh Bank ICT, HITRUST CSF)
- **Evidence Hub** — Upload, malware scan (ClamAV), AI analysis (llava:7b), human review
- **Gap Assessment** — Map evidence to controls, track compliance gaps
- **Risk Register** — Inherent/residual scoring, treatment planning, heatmap
- **Unified Assessment** — Gap-to-final workflow with evidence linking
- **Reporting** — PDF/Excel export, scheduled reports, custom templates
- **Evaluation Harness** — 60-item corpus for thesis evaluation (seeder + runner + CSV export)

---

## Architecture Overview

```
upload → EvidenceFile(scan_status=pending, ai_analysis_status=pending)
       → n8n webhook (responseMode: onReceived)
       → ClamAV :9000/scan
           ├─ infected → quarantine (never deleted)
           └─ clean → Ollama /api/generate (llava:7b, temp 0.1, num_ctx 8192)
                      → 3-stage JSON parse
                      → /api/n8n/ai-callback → awaiting_review
       → human auditor reviews → determination recorded
```

**Fallback:** If n8n is down, `DirectEvidenceAnalysisService` runs the same pipeline synchronously via `AnalyzeEvidenceJob`.

---

## Testing

```bash
php artisan test          # All 316 tests
php artisan test --filter=Project
php artisan test --filter=Evidence
```

---

## Project Structure

```
app/
├── Http/Controllers/           Web + API controllers
├── Models/                     ~88 Eloquent models
├── Services/                   Domain services
│   ├── DirectEvidenceAnalysisService.php
│   ├── EvidenceScanService.php
│   ├── EvaluationRunService.php
│   └── Dashboard/              Query builders for dashboard
├── Modules/
│   ├── RiskManagement/         Risk register, scoring, control mapping
│   └── Compliance/             Control tests, monitors, findings
├── Jobs/                       AnalyzeEvidenceJob, dashboard jobs
├── Console/Commands/           n8n:setup, evidence:resume-analysis, evaluation:*
└── Providers/AuthServiceProvider.php  Gates & policies
```

---

## Configuration Reference

Key `.env` variables:

```env
# Core
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database

# n8n (set N8N_ENABLED=true when docker compose up)
N8N_ENABLED=false
N8N_UNIFIED_WEBHOOK_URL=http://host.docker.internal:5678/webhook/evidence-processing
N8N_WEBHOOK_SECRET=your-secret

# AI
AI_PROVIDER=ollama
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=llava:7b
OLLAMA_TIMEOUT=300

# ClamAV
CLAMAV_API_URL=http://localhost:9000

# Internal URL (for n8n callbacks from Docker)
APP_INTERNAL_URL=http://host.docker.internal:8000
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `llava:7b` not found | `ollama pull llava:7b` (ensure `ollama serve` is running) |
| n8n webhook 404 | Import `unified-evidence-workflow.json` in n8n UI; check `N8N_UNIFIED_WEBHOOK_URL` |
| ClamAV connection refused | `docker compose up -d clamav`; verify `docker ps` shows `clamav_service` |
| Queue not processing | Run `php artisan queue:work` in separate terminal |
| Vite assets 404 | Run `npm run build` (production) or `npm run dev` (development) |
| CSP errors (Alpine.js) | Check `SecurityHeaders` middleware; `unsafe-inline` required for Alpine |
| SQLite locked | Ensure only one `php artisan serve` + one `queue:work` |

---

## License

MIT License — See [LICENSE](LICENSE) for details.

---

## Academic Context

This codebase is the artefact for an MSc thesis (University of Dhaka, Dept. of CSE). The thesis describes this implementation; discrepancies between code and thesis should be reported, not silently patched. All architectural decisions prioritise explainability for viva examination.