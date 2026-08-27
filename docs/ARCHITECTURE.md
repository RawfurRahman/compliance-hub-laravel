# Compliance Hub — Architecture & Feature Analysis

_Generated from a full codebase analysis of `D:\compliance-hub-laravel`._

---

## 1. Overview

**Compliance Hub** is a Laravel 12 / PHP 8.2 GRC (Governance, Risk & Compliance) platform. It supports
compliance frameworks (PCI DSS v4.0.1, ISO 27001:2022, SWIFT CSCF, SOC 2 Type II, HITRUST CSF, BB ICT,
VAPT), Gap/Final assessments, evidence collection backed by an automated AI + antivirus pipeline
(ClamAV → Ollama LLM → human-in-the-loop review → gap consolidation → risk auto-creation), a full Risk
Register with three generations of scoring engines (inherent / residual / financial exposure),
third-party vendor risk, a Compliance Testing & Monitoring module, an executive dashboard, and a
multi-report PDF/email system.

It is a **modular monolith**: two self-contained feature modules (`app/Modules/Compliance`,
`app/Modules/RiskManagement`) sit beside a legacy core (`app/Http`, `app/Services`, `app/Models`).
The frontend is **server-rendered Blade + Alpine.js** (Tailwind + Font Awesome via CDN), with an
**orphaned Vue 3 dashboard** that was built but never mounted.

---

## 2. Tech Stack

| Area | Choice |
|---|---|
| Framework | Laravel 12 (`laravel/framework ^12.0`), PHP `^8.2` |
| Database | SQLite default (`database/database.sqlite`); MySQL/Postgres/SQLServer drivers configured |
| Auth | Session web auth + Laravel Sanctum (API) + 6-digit email OTP |
| Queue / Cache / Session | `database` / `file` / `file` (SQLite-backed) |
| External services | n8n (workflow automation, Docker `:5678`), ClamAV REST (Docker `:9000`), Ollama LLM (`llava:7b`, `:11434`), Mailpit SMTP (`:2525`) |
| Excel / PDF | `maatwebsite/excel`, `barryvdh/laravel-dompdf`, PhpSpreadsheet |
| Frontend | Blade + Alpine.js + Tailwind v4 + Font Awesome (CDN); Vue 3 + ApexCharts (unwired bundle); Vite |
| Rule evaluation | `symfony/expression-language` for monitoring-rule expressions |

**Composer packages** (`composer.json`): `laravel/framework ^12`, `laravel/sanctum ^4.1`,
`laravel/tinker`, `predis/predis ^3`, `maatwebsite/excel ^3.1`, `barryvdh/laravel-dompdf ^3`,
`symfony/expression-language ^6`. Dev: `phpunit ^11.5`, `mockery`, `collision`, `pint`, `pail`, `sail`,
`faker`.

---

## 3. Architecture

### 3.1 Layering

```
routes/
  web.php        Core web routes (auth, dashboard, projects, assessments, PCI, evidence, admin)
  api.php        n8n webhook callbacks + evidence file retrieval
  console.php    Scheduled commands (Laravel 12 style)
app/
  Http/Controllers           22 controllers (legacy core)
  Http/Middleware            1 file (VerifyCsrfToken)
  Policies                   ProjectPolicy
  Services/                  14 services + 9 dashboard query services
  Modules/Compliance/        12 models, 9 controllers, 11 services, routes, provider, events, jobs, commands
  Modules/RiskManagement/    33 models, 14 controllers, 21 services, routes, provider, events, listeners,
                             observers, jobs, commands, 18 API resources, Support/Scoring value objects
  Models/                    42 legacy/core models
  DTOs/                      Dashboard/DashboardFilter
  Support/Reports/           ReportRegistry
  Reports/Generators/        PciDssRocGenerator (+ abstract ReportGenerator)
  Imports/                   4 Excel importers
  Jobs/                      AnalyzeEvidenceJob, dashboard snapshot/cache jobs
  Mail/                      4 mailables
bootstrap/
  app.php        Laravel 12 bootstrap (web+console+health, CSRF exception api/n8n/*)
  providers.php  AppServiceProvider, DashboardServiceProvider
config/
  app.php        ALSO registers AuthServiceProvider, module providers, RouteServiceProvider (hybrid)
  rmm.php        Risk Management config (thresholds, formulas, legacy mapping, matching weights)
  compliance.php Feature flag + payment channels
  n8n.php        n8n endpoints, timeout, retries, signature verification
```

### 3.2 Bootstrap — a Laravel 10/12 hybrid

- `bootstrap/providers.php` (Laravel 12 source of truth) registers only `AppServiceProvider` and
  `DashboardServiceProvider`.
- `config/app.php` (Laravel 10 style) additionally registers `AuthServiceProvider`,
  `RiskManagementServiceProvider`, `ComplianceServiceProvider`, and `RouteServiceProvider`.
- **Verified at runtime**: both lists load — module providers, routes, observers, listeners, and
  commands are all active. The app registers **271 routes** (124 module/dashboard routes, including
  `api/n8n/*` webhooks). The legacy `app/Http/Kernel.php` also exists but is ignored by the Laravel 12
  bootstrap.

### 3.3 Two risk scoring generations coexist

- **Legacy path** — `RiskCalculationService` + `ScoringEngine` (inside the RiskManagement module):
  `TV = T + AV`, `Inherent = AV × TV × LH`, cumulative control effectiveness `1 − ∏(1 − eᵢ/100)`,
  `scoreToLevel()` from heatmap-config thresholds.
- **Modern pure path** — `RiskScoringService` (inherent) + `ResidualRiskService` (residual) backed by
  **versioned, immutable formula configs** (`Support/Scoring/*`): reproducible, auditable scoring with
  appetite status, heatmap coordinates, ranking, trend direction, and manual override. Both paths write
  append-only history to `risk_inherent_scores` / `risk_residual_scores`.

### 3.4 Cross-module coupling

- Compliance `RemediationService` creates RiskManagement `RiskTreatmentPlan` records.
- RiskManagement `AssessmentFindingObserver` auto-creates risks from compliance findings and invalidates
  dashboard caches.
- `DashboardMetricsService` and `app/Services/Dashboard/*` query services span both modules.

---

## 4. Developed Features (detail)

### 4.1 Auth, Roles & Admin

- **Login → email OTP** (6-digit) two-step flow (`AuthController`, `LoginOtpMail`, `verify-otp.blade.php`).
- Roles: **Super Admin, Admin, Auditor, Customer** (`RoleSeeder`). Super Admin bypasses all gates
  (`Gate::before`). Gates: `is-admin`, `is-auditor`, `is-customer`, `view-dashboard`.
- Admin: user CRUD + role assignment, customer-team (sub-user) management, dynamic framework CRUD,
  framework-control CRUD + Excel bulk import, PCI DSS requirement CRUD (260 seeded requirements).
- Artisan wizards: `app:create-super-admin`, `app:create-user`.

### 4.2 Projects

- Project CRUD with `module_type`, assigned users (`project_user`), scope description, and auto-created
  PCI details for PCI projects.
- **Project Hub** (`ProjectHubController`): hub dashboard, scope management, reporting menu, report
  generation/download.

### 4.3 Assessments (Gap → Final) — three implementations

1. `AssessmentController` (legacy, `/assessments/{project}`): two-phase Gap→Final, 100%-Gap-compliant
   gate before Final, findings CRUD, PDF report.
2. `UnifiedAssessmentController` (`/projects/{project}/assessments/{framework_slug}/{type}`): slug-driven,
   auto-initializes one finding per control, auto-clones Gap→Final at 100% (and un-clones on unmark),
   evidence upload/attach/detach.
3. `GapAssessmentController` + `PciGapAssessmentController` + `IsoGapAssessmentController`: generic gap
   assessment grouped by control + PCI/ISO-specific Excel-import gap assessments.

Core engine: `AssessmentService` (initialize, sync findings, Gap→Final deep-clone with
`cloned_from_finding_id`).

### 4.4 Evidence Management & the AI/AV pipeline (largest feature)

`EvidenceController` (~1,200 lines) drives: Evidence Hub, upload (20MB cap), ClamAV malware scanning,
AI gap analysis, human-in-the-loop (HITL) review, evidence chat, feedback, ZIP export, scope toggling,
activity feed.

**Pipeline**: upload → `AnalyzeEvidenceJob` → `EvidenceScanService` (ClamAV → `evidence_scan_logs`,
quarantine infected files to a private disk, never deletes) → n8n callbacks (`POST /api/n8n/scan-callback`,
`ai-callback`) → `DirectEvidenceAnalysisService` (Ollama, parses gaps/observations) → human approve/reject
→ `EvidenceTrackerService` (8-state machine: submit-for-review → approve-with-data → send-to-gap →
pass-to-final → auto-create-risk) → `GapAssessmentReportService` / `AiGapConsolidationService` push
findings into Gap assessments → `RiskAutoCreationService` creates risks.

Supporting pieces: webhook HMAC signature verification, quarantine logging, `/dashboard/scan-stats` panel,
`evidence:resume-stuck-analysis` command (re-dispatches stuck analyses every 10 min).

### 4.5 Compliance Module (Testing & Monitoring)

- **Compliance Tests** catalog: CRUD, framework links, failing entities, 5 n8n integration templates
  seeded, pass-rate accessors, `?view=all|by_framework` views.
- **Control Tests**: pass/fail/partial execution → auto-creates AssessmentFindings.
- **Control Monitoring**: `MonitoringRule` (Symfony `expression-language` via `SafeExpressionEvaluator`)
  + `ControlMonitor` with cron scheduling, consecutive-failure counters, auto-findings, cascading
  updates to linked Compliance Tests (Passing / Overdue / Needs Remediation / …).
- **Compliance Snapshots**: state counts + avg remediation time; `compare()` diffs two snapshots.
- **Audit Findings**: CRUD, scheduling, closing, linking to assessment findings, dashboard aggregation.
- **Remediation Plans**: `RemediationService` creates/closes `RiskTreatmentPlan` from findings;
  `RemediationMetricsService` computes MTTR/MTTA/aging buckets in hours.
- **Integrations**: `IntegrationService` connects an n8n integration → auto-creates ComplianceTests from
  templates for all active frameworks.
- **Mapping Import**: `MappingImportService` imports control↔framework-control mappings with
  `FrameworkVersion` support (idempotent).

### 4.6 Risk Management Module (richest domain)

- **Risk Register**: CRUD, serial_no generation, Excel-exact register view (1,018-line Blade), 5×5
  heatmap, PDF/CSV export, comments, tags, status transitions + full **lifecycle state machine**
  (draft→assessed→accepted→treated→monitoring→closed / escalated / expired), activity-logged transitions.
- **Scoring engines**: legacy `ScoringEngine`/`RiskCalculationService` and modern
  `RiskScoringService`/`ResidualRiskService` (weighted control/treatment/remediation/waiver/evidence
  modifiers, appetite crossing). Auto-recalc via `RiskRecalculationJob` on model `saved`/`deleted` hooks.
- **Financial Exposure** (Prompt 6): `FinancialExposureService` — SLE/ARO/ALE, expected remediation cost,
  business-interruption impact, portfolio exposure; `FinancialExposureMetric` snapshots; unit-tested math.
- **Control Mapping Engine** (Stage 4): `ControlMappingService` fuzzy + keyword weighted suggestions with
  confidence scores, confirm/reject/manual-map, per-risk listing, Excel export/import of cross-framework
  mappings, admin review dashboard.
- **Treatments / Scenarios / Acceptances**: treatment plans (progress, effectiveness, overdue), risk
  scenarios, risk acceptances with expiry (`risks:expire-acceptances` → auto lifecycle expiry).
- **Legacy Migration**: `MigrationService` converts legacy ISO/PCI gap rows into unified `RiskRegister`
  (`rmm:migrate-legacy`, idempotent).
- **Workbook Import/Export**: `WorkbookImportService` (626 lines) — header mapping, dry-run, confirm;
  `RiskRegisterFindingImport`; `ControlMappingSheetExport`.
- **Vendor / Third-Party Risk**: vendors, risk-tiering (tier 1–3), assessments, questionnaire responses,
  weighted scoring, **AI strengths/weaknesses summary** (Ollama) + HITL flag-for-review, completion event
  → AI summary listener.

### 4.7 Dashboard & Analytics

- **Server-rendered enterprise dashboard** (`dashboard.blade.php` + Alpine): KPIs, heatmap, top risks,
  maturity score, inherent-vs-residual, control effectiveness, compliance scorecard, risk-by-department,
  issues/remediation, risk-acceptance split, ClamAV scan-stats.
- **JSON API v1** (`Api/DashboardApiController` + 6 `BaseQueryService` subclasses): per-domain caching
  (KPI / HEATMAP / RISK_RANKING / COMPLIANCE_SCORECARD / THIRD_PARTY_RISK / REMEDIATION_TREND), filters via
  `DashboardFilter` DTO, `DashboardSnapshot` persistence, refresh jobs/commands.
- **Maturity Score Engine**: 4-dimension 1–5 GRC maturity, weekly `MaturityScoreSnapshot`
  (`maturity:snapshot`).
- 18 JSON API Resources feed a **Vue 3 + ApexCharts dashboard** (KPI cards, heatmap grid, donuts, trend
  charts, aging bars) — built but never mounted (see Issues).

### 4.8 Reports

- `ReportRegistry` declares 3 PCI DSS reports (ROC, AOC, Gap) + dynamic unified Gap/Final reports for
  non-PCI projects (disabled until a Gap assessment exists).
- `ReportGenerationService` orchestrates; `PciDssRocGenerator` (abstract `ReportGenerator` base) eagerly
  loads PCI project details and computes compliance metrics; `ReportExportService` renders PDF
  (DomPDF)/HTML and records `GeneratedReport` rows.
- **Scheduling + email**: `ReportSchedule` model, `compliance:send-scheduled-reports` daily command,
  `ComplianceReportMail` with attachments, `ReportSchedule::calculateNextRun()`.
- **Custom report templates** with filters/sections; reporting dashboard metrics (snapshots, totals,
  trends).

### 4.9 Other Features

- **Meetings**: scheduling + invitation/reschedule emails (`MeetingInvitationMail`).
- **Required Documents**: DOCX/XLSX/CSV import of required-document lists
  (`RequiredDocumentListImportService`).
- **Chat**: evidence chat messages per project.
- **Activity Log**: append-only, auto role capture.

### 4.10 Jobs, Scheduling & Tests

- **Jobs (8)**: `AnalyzeEvidenceJob`, `RefreshDashboardSnapshotJob`, `InvalidateDashboardCacheJob` (dead),
  `RiskRecalculationJob`, `VendorAssessmentScoringJob` (dead), `RunMonitoringChecksJob`,
  `GenerateComplianceSnapshotJob`.
- **Scheduled** (`routes/console.php`): resume-stuck-evidence (10 min), scheduled reports (daily),
  maturity (weekly), dashboard snapshots (daily/weekly/monthly), executive/financial/remediation metric
  snapshots (daily 01:00–03:00), cache invalidation (5 min, 06:00–22:00), monitoring checks (15 min,
  08:00–18:00).
- **Tests**: 38 files (~250+ cases) covering assessments, evidence pipeline (ClamAV quarantine, AI gaps,
  HITL), dashboard analytics + RBAC + caching, risk lifecycle/scoring/notes/snapshots/treatment/exposure,
  control-mapping engine (web + API + Excel), vendor AI analysis, compliance
  snapshots/monitoring/remediation/audit findings, workbook parity, and pure-unit math tests for the
  scoring engines. Config: in-memory SQLite, sync queue, array cache.

---

## 5. Issues & Recommended Fixes

### 🔴 Critical / High Priority

| # | Issue | Location | Fix |
|---|---|---|---|
| 1 | **Schema drift** — repo has 25 migration files but the DB `migrations` table has 100 rows and ~130 tables. ~16 models point to tables missing everywhere; ~30 DB tables are orphaned. | `database/migrations/`, `database/database.sqlite` | Consolidate/regenerate migrations to match reality, or adopt a baseline dump + incremental migrations. Highest-impact item. |
| 2 | **Broken routes → non-existent methods** — `attachEvidence`, `addTreatment`, `deleteTreatment`, `submitReview`, `addKri`, `deleteKri` don't exist on `RiskRegisterController`. Any hit throws `BadMethodCallException`. | `app/Modules/RiskManagement/Routes/web.php:23,29–33` | Remove routes or implement the methods. |
| 3 | **Missing `use` import** — `ProfileController` used unqualified (no import). Breaks `/profile` & `/settings`; `php artisan route:list` throws `ReflectionException: Class "ProfileController" does not exist`. | `routes/web.php:200–203` | Add `use App\Http\Controllers\ProfileController;`. |
| 4 | **Wrong model namespace** — imports `App\Modules\RiskManagement\Models\Project` (doesn't exist). | `app/Modules/RiskManagement/Controllers/RiskSnapshotController.php:5` | Change to `App\Models\Project`. |
| 5 | **Models with no backing table** (any query fails): `Requirement`, `RiskRegisterEntry`, `RiskCategory`, `RiskDepartment`, `RiskAsset`, `RiskAppetite`, `RiskAcceptanceRequest`, `RiskExceptionRequest`, `RiskReview`, `RiskReviewCycle`, `RiskKpiMetric`, `RiskKriMetric`, `RiskNotification`, `RiskStatusHistory`, `RiskAttachment`, `RiskTreatment`. | `app/Modules/RiskManagement/Models/*` | Add migrations or remove the models. |

### 🟠 Medium Priority

| # | Issue | Location |
|---|---|---|
| 6 | Dead jobs (never dispatched): `InvalidateDashboardCacheJob`, `VendorAssessmentScoringJob`. | `app/Jobs/Dashboard/`, `app/Modules/RiskManagement/Jobs/` |
| 7 | Dead events (never dispatched): `RiskExposureRecalculated`, `VendorRiskEscalated`. Compliance module's 5 events have no listeners. | `app/Modules/RiskManagement/Events/`, `app/Modules/Compliance/Events/` |
| 8 | Orphaned Vue 3 dashboard — built with matching API endpoints, no Blade view mounts `#dashboard-app`. | `resources/js/dashboard/*` |
| 9 | Stale Vite inputs — `vite.config.js` references non-existent `resources/js/dashboard/governance.js`; `@vite` used on only 3 views; runtime uses CDN Tailwind/Alpine. | `vite.config.js` |
| 10 | Duplicate `RemediationMetricsService` in both modules. | `app/Modules/Compliance/Services/`, `app/Modules/RiskManagement/Services/` |
| 11 | `RemediationMetric` model fillable doesn't match live DB columns. | `app/Modules/RiskManagement/Models/RemediationMetric.php` |
| 12 | `guest` middleware maps to non-existent `RedirectIfAuthenticated` (harmless until used/cache:route). | `app/Http/Kernel.php:60` |
| 13 | Three parallel assessment stacks (Assessment / Unified / Gap + PCI/ISO gap) and two parallel dashboard APIs (Blade vs `/api/v1/dashboard`). | `routes/web.php`, `app/Http/Controllers/*` |
| 14 | Tests manually rebuild tables via raw `Schema` in `setUp()` — symptom of schema drift. | `tests/Feature/Stage14EvidenceGapCheckTest.php`, `VendorAssessmentAiAnalysisTest.php`, `Stage6ControlTestMappingTest.php` |
| 15 | `.env` diverges from `.env.example`; two SQLite DBs exist (`database.sqlite`, `compliance_hub.sqlite`). | root |

### 🟡 Low Priority / Hygiene

- Docs stale / risk-centric: `docs/er-diagram.md` predates evidence/vendor/compliance tables;
  `docs/data-dictionary.csv` covers only the risk workbook contract; `README.md` is the untouched Laravel
  default.
- No factories beyond `UserFactory` despite widespread `HasFactory`.
- `DatabaseSeeder` doesn't run `WorkbookRiskSeeder` (registered separately via `rmm:seed`).
- Module providers claim `loadMigrationsFrom(database_path('migrations'))` but no module `Database/`
  directory exists (misleading).
- Root diagnostic scripts / Node mock servers (`mock_n8n.cjs`, `clamav-rest-server.js`) should move to
  `devtools/`; `compliance-hub-laravel.rar` and log files should be git-ignored.

---

## 6. Appendix

### 6.1 Route inventory

- **Core web** (`routes/web.php`, 207 lines): auth/OTP, dashboard + analytics, dashboard JSON API v1
  (`api/v1/dashboard/*`), project CRUD + hub + scope, gap/unified/legacy assessments, PCI/ISO gap,
  reporting, PCI DSS, evidence hub/tracker/chat, required documents, meetings, customer team,
  admin (users, requirements, frameworks, controls), profile/settings.
- **Core api** (`routes/api.php`): `GET /user`, n8n callbacks, evidence file retrieval.
- **Compliance module**: `projects/{project}/compliance/*` (dashboard, tests, findings, remediations,
  snapshots, audit findings, integrations) + `api/compliance/*`.
- **RiskManagement module**: `projects/{project}/risk-register/*`, `projects/{project}/vendors/*`,
  `admin/controls/*`, `admin/control-mappings` + `api/rmm/*`.

### 6.2 Data drift snapshot

- **In repo migrations** (~25 files, covering): users/roles/sessions, projects, frameworks/controls,
  assessments/findings, evidence (files, scan logs, workflow logs, tracker fields), PCI DSS requirements,
  risk register (~50 cols), financial exposure metrics, vendor assessments + questionnaire responses,
  compliance tests/monitoring/integrations/templates, assets, pivots.
- **In DB only (no migration, no model)**: policy family (`policies`, `policy_versions`, `policy_approvals`,
  `policy_reviews`, `policy_publications`, `policy_waivers`, `policy_exceptions`), trust center family,
  governance/SLA family (`domains`, `governance_metric_snapshots`, `sla_rules`, `comp_sla_trackers`,
  `ownership_matrix`, `stakeholders`), `pci_reviewed_environment_tables`.
- **Model only (no migration, no DB table)**: see Issue #5 (16 models).

### 6.3 Command schedule (`routes/console.php`)

| Command | Frequency |
|---|---|
| `evidence:resume-stuck-analysis` | every 10 minutes |
| `compliance:send-scheduled-reports` | daily |
| `maturity:snapshot` | weekly |
| `dashboard:refresh-snapshots --date-scope=daily` | daily 01:00 |
| `risks:snapshot-executive-metrics` | daily 02:00 |
| `risks:snapshot-financial-metrics` | daily 02:30 |
| `risks:snapshot-remediation-metrics` | daily 03:00 |
| `dashboard:refresh-snapshots --date-scope=weekly` | weekly |
| `dashboard:refresh-snapshots --date-scope=monthly` | monthly |
| `dashboard:invalidate-cache` | every 5 min, 06:00–22:00 |
| `compliance:run-monitoring-checks` | every 15 min, 08:00–18:00 |
