# Compliance Hub - Report Menu Implementation Plan

## Executive Summary
The compliance hub currently has a basic PCI DSS ROC (Report on Compliance) template implementation. This plan outlines how to create a comprehensive multi-report menu system that supports PCI DSS v4.0.1 and other compliance frameworks.

---

## Current State Analysis

### What Exists
- ✅ **ReportController** (app/Http/Controllers/ReportController.php)
  - Single method: `generate($project)` - generates PCI DSS ROC report
  - Route: `GET /reports/pci/{project}` named `reports.pci.generate`
  
- ✅ **PCI DSS Report View** (resources/views/pci/report.blade.php)
  - Styled for A4 print format
  - Includes Part I: Assessment Overview
  - Print/PDF export capability
  
- ✅ **Data Models**
  - Project (with module_type: 'pci_dss', etc.)
  - ProjectPciDssDetail (stores PCI-specific metadata)
  - PciDssFinding, PciDssRequirement, etc.
  - Framework model (with version support)

- ✅ **Routes Infrastructure**
  - `/projects/{project}/reporting` - reporting hub (referenced but controller missing)
  - `/projects/{project}/reporting/{type}` - specific report (referenced but controller missing)

### What's Missing
- ❌ **ProjectHubController** - Core controller for project management
- ❌ **Report Menu/Dashboard** - Centralized reporting interface
- ❌ **Multi-Report Support** - Only PCI DSS exists; need ISO 27001, SOC 2, HIPAA, etc.
- ❌ **Report Templates** - Additional compliance framework templates
- ❌ **Report Generation Service** - Abstracted logic for different report types
- ❌ **Report History/Tracking** - Audit trail of generated reports
- ❌ **Export Formats** - PDF, Word, Excel generation for reports

---

## Implementation Architecture

### 1. Report Service Layer
**Location**: `app/Services/ReportGenerationService.php`

```php
class ReportGenerationService {
    - generate(Project $project, string $reportType, array $options)
    - getAvailableReports(Project $project): Collection
    - validateReportType(Project $project, string $type): bool
}
```

### 2. Report Types Registry
**Location**: `app/Support/Reports/ReportRegistry.php`

Define report types and their associated:
- Class/Generator
- View template
- Required data models
- Export formats supported
- Permissions required

```php
$registry = [
    'pci_dss' => [
        'class' => PciDssRocReport::class,
        'label' => 'Report on Compliance (ROC)',
        'version' => '4.0.1',
        'description' => 'Official PCI DSS Assessment Report',
        'view' => 'reports.pci.roc',
        'exports' => ['pdf', 'html'],
        'frameworks' => ['pci_dss'],
    ],
    'pci_dss_attestation' => [
        'class' => PciDssAttestationReport::class,
        'label' => 'Attestation of Compliance (AOC)',
        'version' => '4.0.1',
        'view' => 'reports.pci.aoc',
        'exports' => ['pdf'],
        'frameworks' => ['pci_dss'],
    ],
    // More reports...
];
```

### 3. Report Generator Abstractions
**Location**: `app/Reports/Generators/`

Each framework has specific generators:
```
ReportGenerators/
├── PciDssRocGenerator.php
├── PciDssAocGenerator.php
├── PciDssGapAssessmentGenerator.php
├── Iso27001ReportGenerator.php
├── Soc2ReportGenerator.php
└── HipaaReportGenerator.php
```

### 4. ProjectHubController
**Location**: `app/Http/Controllers/ProjectHubController.php`

Methods:
- `show(Project $project)` - Project hub dashboard
- `reporting(Project $project)` - Reporting menu/overview
- `report(Project $project, string $type)` - Generate specific report
- `downloadReport(Project $project, string $type, string $format)` - Download export
- `listReports(Project $project)` - API endpoint for available reports

### 5. Report Menu Views
**Location**: `resources/views/projects/`

```
resources/views/
├── projects/
│   ├── hub.blade.php              (Project hub dashboard)
│   ├── reporting/
│   │   ├── menu.blade.php         (Report selection menu)
│   │   ├── overview.blade.php     (Reports overview/history)
│   │   └── generate.blade.php     (Report generation status)
│   └── ...
└── reports/
    ├── pci/
    │   ├── roc.blade.php          (ROC template)
    │   └── aoc.blade.php          (AOC template)
    ├── iso27001/
    │   └── statement.blade.php
    ├── soc2/
    │   └── report.blade.php
    └── shared/
        └── components.blade.php   (Reusable components)
```

### 6. Report History Model
**Location**: `app/Models/GeneratedReport.php`

Track all generated reports:
```php
- id
- project_id
- report_type
- generated_by (user_id)
- generated_at
- data (JSON snapshot of data used)
- file_path (for storage)
- status (draft, final, archived)
- metadata (version, framework_version, etc.)
```

---

## PCI DSS v4.0.1 ROC Template Structure

### Part I: Assessment Overview
- 1. Contact Information and Summary of Results
- 2. Scope of Assessment  
- 3. Assessment Activities and Timeframe
- 4. Assessor Declaration

### Part II: Attestation of Compliance
- 5. Overall Results (Pass/Fail per requirement)
- 6. Compensating Controls Summary

### Part III: Detailed Findings
- 7. Requirement-by-Requirement Analysis
  - Requirement summary
  - Testing methodology
  - Findings (Pass/Not Tested/Not Applicable)
  - Evidence references
  - Risk ratings (if applicable)

### Part IV: Appendices
- A. Assessment Scope & Network Diagrams
- B. Payment Channels
- C. Tested Systems/Components
- D. External Vulnerability Scan Results
- E. Quarterly Internal Scan Results
- F. Conclusion and Signature

---

## Database Migrations

### Migration 1: Create Generated Reports Table
```sql
CREATE TABLE generated_reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT
    project_id BIGINT NOT NULL
    report_type VARCHAR(255) NOT NULL
    framework_slug VARCHAR(255)
    framework_version VARCHAR(50)
    generated_by BIGINT NOT NULL
    generated_at TIMESTAMP
    exported_formats JSON
    status ENUM('draft', 'final', 'archived')
    metadata JSON
    created_at, updated_at
);
```

### Migration 2: Enhance project_pci_dss_details
Add fields for report metadata:
- signature_date
- assessor_name
- assessor_title
- company_address
- merchant_category_code
- etc.

---

## Implementation Phases

### Phase 1: Foundation (Week 1)
- [ ] Create ProjectHubController
- [ ] Create reporting menu view
- [ ] Refactor ReportController to use service layer
- [ ] Create GeneratedReport model & migration
- [ ] Add report menu UI to project cards

### Phase 2: PCI DSS Enhancement (Week 2)
- [ ] Create PciDssRocGenerator service
- [ ] Enhance PCI ROC view with all sections
- [ ] Add PDF export capability
- [ ] Create report history tracking
- [ ] Add report download functionality

### Phase 3: Multi-Framework Support (Week 3)
- [ ] Create ReportRegistry
- [ ] Implement generators for ISO 27001, SOC 2
- [ ] Create framework-specific templates
- [ ] Add report comparison view
- [ ] Create export formats (HTML, PDF, Word)

### Phase 4: Advanced Features (Week 4)
- [ ] Report scheduling/automation
- [ ] Report templates management
- [ ] Custom report builder
- [ ] Report email distribution
- [ ] Compliance dashboard with report metrics

---

## API Endpoints

### Reporting Endpoints
```
GET    /projects/{project}/reporting              → reporting menu
GET    /projects/{project}/reporting/{type}       → view report
POST   /projects/{project}/reporting/{type}/generate → regenerate
GET    /projects/{project}/reporting/{type}/download → download
GET    /projects/{project}/reports/history        → list all reports
GET    /api/projects/{project}/available-reports  → JSON list
```

---

## Key Files to Create/Modify

### Create
1. `app/Http/Controllers/ProjectHubController.php`
2. `app/Services/ReportGenerationService.php`
3. `app/Reports/Generators/PciDssRocGenerator.php`
4. `app/Support/Reports/ReportRegistry.php`
5. `app/Models/GeneratedReport.php`
6. Database migration for generated_reports table
7. Multiple Blade views for reporting UI
8. Report templates for each framework

### Modify
1. `routes/web.php` - Wire up ProjectHubController routes
2. `app/Http/Controllers/ReportController.php` - Refactor to use services
3. `app/Models/Project.php` - Add relationship to GeneratedReport
4. `resources/views/projects/index.blade.php` - Add report menu link

---

## Configuration

Add to `config/compliance.php`:
```php
'report_types' => [
    'pci_dss' => [
        'enabled' => true,
        'label' => 'PCI DSS ROC',
        'versions' => ['4.0.1'],
        'export_formats' => ['pdf', 'html'],
        'requires_pci_details' => true,
    ],
    // More types...
],

'report_templates' => [
    'pci_dss' => [
        'pdf_engine' => 'dompdf', // or 'wkhtmltopdf'
        'include_signatures' => true,
        'include_appendices' => true,
    ],
],
```

---

## Success Criteria

✅ Users can navigate to project → reporting menu
✅ Menu displays all available reports for the project's framework
✅ Users can generate PCI DSS ROC report with one click
✅ Reports can be viewed in browser and downloaded as PDF
✅ Report history is tracked and visible
✅ System supports multiple compliance frameworks
✅ Reports match official compliance templates (v4.0.1 for PCI)
✅ Reports are accessible only to authorized users
✅ Report data is versioned and can be regenerated

---

## Risk & Mitigation

| Risk | Mitigation |
|------|-----------|
| Large PDF generation time | Queue long-running jobs with Bull/Redis |
| Report storage bloat | Archive old reports, compress exports |
| Data accuracy | Version snapshots of assessment data |
| Compliance validation | Review templates against official specs |
| Access control | Audit who generated/viewed each report |

