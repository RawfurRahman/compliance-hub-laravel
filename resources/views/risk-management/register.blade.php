@extends('layouts.app')

@push('styles')
<style>
/* ======================================================
   INTEGRATED RISK REGISTER — Excel-exact styling
   Matches the uploaded Cybersecurity Compliance Hub
   Risk Register spreadsheet
   ====================================================== */

/* Register container: full-width overflow */
.rr-container { width: 100%; overflow: hidden; font-family: 'Inter', sans-serif; }

/* ── Header banner ── */
.rr-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    color: #fff;
    text-align: center;
    padding: 12px 24px;
    border-radius: 8px 8px 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.rr-header h1 { font-size: 15px; font-weight: 800; letter-spacing: 0.05em; margin: 0; text-transform: uppercase; }

/* ── KPI cards ── */
.rr-kpi-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin: 12px 0; }
.rr-kpi { background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 12px; }
.rr-kpi .label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.rr-kpi .value { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px; }
.rr-kpi.critical .value { color: #c0392b; }
.rr-kpi.high     .value { color: #e67e22; }
.rr-kpi.medium   .value { color: #d4a017; }
.rr-kpi.low      .value { color: #27ae60; }

/* ── Toolbar ── */
.rr-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    gap: 10px;
    flex-wrap: wrap;
}
.rr-toolbar-left { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.rr-search {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 6px 10px 6px 30px;
    font-size: 12px;
    width: 220px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") no-repeat 9px center;
    outline: none;
}
.rr-filter-select {
    border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px;
    font-size: 12px; background: #fff; outline: none; cursor: pointer;
}
.rr-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: 8px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em; cursor: pointer; border: none; transition: all .15s;
    text-decoration: none;
}
.rr-btn-primary   { background: #0f172a; color: #fff; }
.rr-btn-primary:hover { background: #1e293b; color: #fff; }
.rr-btn-success   { background: #059669; color: #fff; }
.rr-btn-success:hover { background: #047857; color: #fff; }
.rr-btn-info      { background: #0ea5e9; color: #fff; }
.rr-btn-info:hover { background: #0284c7; color: #fff; }
.rr-btn-warning   { background: #d97706; color: #fff; }
.rr-btn-outline   { background: #fff; color: #374151; border: 1px solid #d1d5db; }
.rr-btn-outline:hover { background: #f1f5f9; }
.rr-btn-danger    { background: #dc2626; color: #fff; }

/* ── Table wrapper ── */
.rr-table-wrap {
    overflow-x: auto;
    border-radius: 0 0 8px 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    max-height: 72vh;
    overflow-y: auto;
}
.rr-table {
    border-collapse: collapse;
    font-size: 11px;
    min-width: 2200px;
    width: 100%;
}
/* Sticky header */
.rr-table thead tr { position: sticky; top: 0; z-index: 10; }

/* ── Group headers (row 1) ── */
.rr-th-section {
    padding: 7px 6px;
    text-align: center;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    border: 1px solid #cbd5e1;
    white-space: nowrap;
}
.rr-th-meta     { background: #1e293b; color: #fff; }           /* identification */
.rr-th-assess   { background: #1d4ed8; color: #fff; }           /* risk assessment (blue) */
.rr-th-treat    { background: #7c3aed; color: #fff; }           /* treatment plan (purple) */
.rr-th-residual { background: #065f46; color: #fff; }           /* residual risk (green) */

/* ── Column headers (row 2) ── */
.rr-th {
    background: #f8fafc;
    color: #374151;
    padding: 6px 8px;
    font-size: 10px;
    font-weight: 700;
    text-align: center;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
}
.rr-th:hover { background: #e2e8f0; }
.rr-th.frozen { position: sticky; left: 0; background: #f1f5f9; z-index: 5; }

/* ── Data cells ── */
.rr-td {
    padding: 5px 8px;
    border: 1px solid #e8ecf0;
    vertical-align: middle;
    white-space: nowrap;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #1e293b;
}
.rr-td.frozen { position: sticky; left: 0; background: #fff; z-index: 3; border-right: 2px solid #cbd5e1; }
.rr-td.num    { text-align: center; font-weight: 600; }
.rr-td.mono   { font-family: monospace; font-weight: 700; }
.rr-tr:hover .rr-td { background: #f0f9ff !important; }
.rr-tr:hover .rr-td.frozen { background: #e0f2fe !important; }

/* ── Risk level badges / score cells ── */
.risk-badge {
    display: inline-block; padding: 2px 8px; border-radius: 4px;
    font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
}
.risk-critical { background: #c0392b; color: #fff; }
.risk-high     { background: #e67e22; color: #fff; }
.risk-medium   { background: #f1c40f; color: #333; }
.risk-low      { background: #27ae60; color: #fff; }

/* Score cell colour fills (match Excel heatmap) */
.score-critical { background: #c0392b !important; color: #fff !important; font-weight: 800; text-align: center; }
.score-high     { background: #e67e22 !important; color: #fff !important; font-weight: 800; text-align: center; }
.score-medium   { background: #f1c40f !important; color: #333 !important; font-weight: 800; text-align: center; }
.score-low      { background: #27ae60 !important; color: #fff !important; font-weight: 800; text-align: center; }

/* Treatment decision badges */
.treat-accepted    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.treat-not-accepted{ background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.treat-review      { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

/* Status badges */
.status-open       { background: #fee2e2; color: #991b1b; }
.status-mitigating { background: #dbeafe; color: #1d4ed8; }
.status-accepted   { background: #dcfce7; color: #166534; }
.status-closed     { background: #f1f5f9; color: #64748b; }
.status-draft      { background: #fef9c3; color: #713f12; }

/* Communication status */
.comm-done    { background: #d1fae5; color: #065f46; }
.comm-pending { background: #fef3c7; color: #92400e; }

/* ── Modal overlay ── */
.rr-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 1000; padding: 20px;
}
.rr-modal {
    background: #fff; border-radius: 12px;
    width: 100%; max-width: 760px;
    max-height: 92vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
}
.rr-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    color: #fff; border-radius: 12px 12px 0 0;
}
.rr-modal-body { padding: 20px; }
.rr-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.rr-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.rr-form-full { grid-column: 1 / -1; }
.form-label-sm { display: block; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.form-input-sm { width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 10px; font-size: 12px; outline: none; transition: border-color .15s; }
.form-input-sm:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.2); }
.form-section-title {
    font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em;
    color: #1d4ed8; border-bottom: 2px solid #dbeafe; padding-bottom: 4px; margin: 16px 0 10px;
}

/* ── Score display ── */
.score-display {
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px; height: 48px; font-size: 22px; font-weight: 900;
    transition: all .3s;
}

/* Toast */
.rr-toast {
    position: fixed; bottom: 24px; right: 24px;
    background: #0f172a; color: #fff;
    padding: 12px 18px; border-radius: 10px;
    font-size: 13px; font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,.3);
    z-index: 9999; display: flex; align-items: center; gap: 8px;
}
</style>
@endpush

@section('content')
<div x-data="riskRegister()" class="rr-container">

    {{-- ── HEADER BANNER ── --}}
    <div class="rr-header">
        <i class="fas fa-shield-alt text-sky-400 text-xl"></i>
        <h1>Cybersecurity Compliance Hub &mdash; Integrated Risk Register</h1>
        <i class="fas fa-shield-alt text-sky-400 text-xl"></i>
    </div>

    {{-- ── KPI CARDS ── --}}
    <div class="rr-kpi-grid">
        <div class="rr-kpi">
            <div class="label">Total Risks</div>
            <div class="value" x-text="kpis.total">{{ $kpis['total'] }}</div>
        </div>
        <div class="rr-kpi critical">
            <div class="label">Critical</div>
            <div class="value" x-text="kpis.critical">{{ $kpis['critical'] }}</div>
        </div>
        <div class="rr-kpi high">
            <div class="label">High</div>
            <div class="value" x-text="kpis.high">{{ $kpis['high'] }}</div>
        </div>
        <div class="rr-kpi medium">
            <div class="label">Medium</div>
            <div class="value" x-text="kpis.medium">{{ $kpis['medium'] }}</div>
        </div>
        <div class="rr-kpi low">
            <div class="label">Low</div>
            <div class="value" x-text="kpis.low">{{ $kpis['low'] }}</div>
        </div>
        <div class="rr-kpi">
            <div class="label">Open</div>
            <div class="value" x-text="kpis.open">{{ $kpis['open'] }}</div>
        </div>
        <div class="rr-kpi">
            <div class="label">Control Eff.</div>
            <div class="value" x-text="kpis.controlEff + '%'">{{ $kpis['controlEff'] }}%</div>
        </div>
    </div>

    {{-- ── TOOLBAR ── --}}
    <div class="rr-toolbar">
        <div class="rr-toolbar-left">
            <input x-model="search" type="text" placeholder="&#128269; Search risks..."
                   class="rr-search" id="rr-search">
            <select x-model="filterLevel" class="rr-filter-select" id="rr-filter-level">
                <option value="">All Levels</option>
                <option value="Critical">Critical</option>
                <option value="High">High</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
            </select>
            <select x-model="filterStatus" class="rr-filter-select" id="rr-filter-status">
                <option value="">All Statuses</option>
                @foreach(\App\Modules\RiskManagement\Models\RiskRegister::STATUSES as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
            <select x-model="filterDept" class="rr-filter-select" id="rr-filter-dept">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->name }}">{{ $d->name }}</option>
                @endforeach
            </select>
            <select x-model="filterTreatment" class="rr-filter-select" id="rr-filter-treat">
                <option value="">All Decisions</option>
                @foreach(\App\Modules\RiskManagement\Models\RiskRegister::TREATMENT_DECISIONS as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('risk-register.heatmap', $project) }}" class="rr-btn rr-btn-info">
                <i class="fas fa-fire"></i> Heat Map
            </a>
            <button @click="openAddModal()" class="rr-btn rr-btn-primary" id="btn-add-risk">
                <i class="fas fa-plus"></i> Add Risk
            </button>
            <a href="{{ route('risk-register.export-csv', $project) }}" class="rr-btn rr-btn-success">
                <i class="fas fa-file-csv"></i> CSV
            </a>
            <a href="{{ route('risk-register.export-pdf', $project) }}" class="rr-btn rr-btn-warning" target="_blank">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>

    {{-- ── RISK REGISTER TABLE ── --}}
    <div class="rr-table-wrap" id="risk-register-table">
        <table class="rr-table" id="rr-main-table">
            <thead>
                {{-- Row 1: Section group headers --}}
                <tr>
                    <th class="rr-th-section rr-th-meta" colspan="8">
                        Risk Identification
                    </th>
                    <th class="rr-th-section rr-th-assess" colspan="9">
                        Risk Assessment
                    </th>
                    <th class="rr-th-section rr-th-treat" colspan="7">
                        Risk Treatment Plan
                    </th>
                    <th class="rr-th-section rr-th-residual" colspan="5">
                        Residual Risk
                    </th>
                </tr>
                {{-- Row 2: Column headers --}}
                <tr>
                    {{-- Identification --}}
                    <th class="rr-th frozen" style="left:0;min-width:35px;" @click="sort('index')">#</th>
                    <th class="rr-th" @click="sort('risk_id')" style="min-width:80px;">Risk ID</th>
                    <th class="rr-th" @click="sort('risk_name')" style="min-width:160px;">Risk Name</th>
                    <th class="rr-th" @click="sort('department')" style="min-width:120px;">Department</th>
                    <th class="rr-th" @click="sort('risk_owner')" style="min-width:100px;">Risk Owner</th>
                    <th class="rr-th" @click="sort('date_identified')" style="min-width:90px;">Assess. Date</th>
                    <th class="rr-th" @click="sort('asset_id_ref')" style="min-width:80px;">Asset ID</th>
                    <th class="rr-th" @click="sort('category_name')" style="min-width:100px;">Category</th>
                    {{-- Assessment --}}
                    <th class="rr-th" style="min-width:200px;">Risk Description</th>
                    <th class="rr-th" @click="sort('threat_score')" style="min-width:60px;">Threat</th>
                    <th class="rr-th" @click="sort('confidentiality')" style="min-width:38px;">C</th>
                    <th class="rr-th" @click="sort('integrity')" style="min-width:38px;">I</th>
                    <th class="rr-th" @click="sort('availability')" style="min-width:38px;">A</th>
                    <th class="rr-th" @click="sort('likelihood')" style="min-width:65px;">Likelihood</th>
                    <th class="rr-th" @click="sort('impact')" style="min-width:65px;">Impact</th>
                    <th class="rr-th" @click="sort('inherent_score')" style="min-width:65px;">Inherent Score</th>
                    <th class="rr-th" @click="sort('inherent_risk_level')" style="min-width:80px;">Risk Level</th>
                    {{-- Treatment --}}
                    <th class="rr-th" style="min-width:160px;">Recommended Control</th>
                    <th class="rr-th" @click="sort('treatment_decision')" style="min-width:100px;">Decision</th>
                    <th class="rr-th" @click="sort('communication_status')" style="min-width:90px;">Comm. Status</th>
                    <th class="rr-th" @click="sort('target_date')" style="min-width:90px;">Target Date</th>
                    <th class="rr-th" @click="sort('next_review_date')" style="min-width:90px;">Review Date</th>
                    <th class="rr-th" @click="sort('status')" style="min-width:90px;">Impl. Status</th>
                    {{-- Residual --}}
                    <th class="rr-th" @click="sort('residual_likelihood')" style="min-width:65px;">Res. Likelihood</th>
                    <th class="rr-th" @click="sort('residual_impact')" style="min-width:65px;">Res. Impact</th>
                    <th class="rr-th" @click="sort('residual_score')" style="min-width:65px;">Res. Score</th>
                    <th class="rr-th" @click="sort('residual_risk_level')" style="min-width:80px;">Res. Level</th>
                    <th class="rr-th" style="min-width:140px;">Follow-up Notes</th>
                    {{-- Actions --}}
                    <th class="rr-th" style="min-width:90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, idx) in filteredRows" :key="row.id">
                    <tr class="rr-tr">
                        <td class="rr-td frozen num" x-text="idx + 1"></td>
                        <td class="rr-td mono" x-text="row.risk_id"></td>
                        <td class="rr-td" :title="row.risk_name" x-text="row.risk_name"></td>
                        <td class="rr-td" x-text="row.department"></td>
                        <td class="rr-td" x-text="row.risk_owner"></td>
                        <td class="rr-td" x-text="row.date_identified"></td>
                        <td class="rr-td" x-text="row.asset_id_ref ?? '—'"></td>
                        <td class="rr-td" x-text="row.category_name ?? '—'"></td>
                        {{-- Assessment --}}
                        <td class="rr-td" style="max-width:200px;white-space:normal;font-size:10px;"
                            :title="row.risk_description" x-text="row.risk_description"></td>
                        <td class="rr-td num" x-text="row.threat_score"></td>
                        <td class="rr-td num" x-text="row.confidentiality"></td>
                        <td class="rr-td num" x-text="row.integrity"></td>
                        <td class="rr-td num" x-text="row.availability"></td>
                        <td class="rr-td num" x-text="likelihoodLabel(row.likelihood)"></td>
                        <td class="rr-td num" x-text="impactLabel(row.impact)"></td>
                        <td class="rr-td num"
                            :class="scoreCssClass(row.inherent_score)"
                            x-text="row.inherent_score"></td>
                        <td class="rr-td">
                            <span class="risk-badge"
                                  :class="'risk-' + row.inherent_risk_level.toLowerCase()"
                                  x-text="row.inherent_risk_level"></span>
                        </td>
                        {{-- Treatment --}}
                        <td class="rr-td" style="max-width:160px;white-space:normal;font-size:10px;"
                            x-text="row.recommended_control ?? '—'"></td>
                        <td class="rr-td">
                            <span class="risk-badge"
                                  :class="treatmentCss(row.treatment_decision)"
                                  x-text="row.treatment_decision"></span>
                        </td>
                        <td class="rr-td">
                            <span class="risk-badge"
                                  :class="row.communication_status === 'Communicated' ? 'comm-done' : 'comm-pending'"
                                  x-text="row.communication_status ?? 'Pending'"></span>
                        </td>
                        <td class="rr-td" x-text="row.target_date ?? '—'"></td>
                        <td class="rr-td" x-text="row.next_review_date ?? '—'"></td>
                        <td class="rr-td">
                            <span class="risk-badge"
                                  :class="statusCss(row.status)"
                                  x-text="row.status"></span>
                        </td>
                        {{-- Residual --}}
                        <td class="rr-td num" x-text="likelihoodLabel(row.residual_likelihood)"></td>
                        <td class="rr-td num" x-text="impactLabel(row.residual_impact)"></td>
                        <td class="rr-td num"
                            :class="scoreCssClass(row.residual_score)"
                            x-text="row.residual_score"></td>
                        <td class="rr-td">
                            <span class="risk-badge"
                                  :class="'risk-' + row.residual_risk_level.toLowerCase()"
                                  x-text="row.residual_risk_level"></span>
                        </td>
                        <td class="rr-td" style="max-width:140px;white-space:normal;font-size:10px;"
                            x-text="row.follow_up_notes ?? '—'"></td>
                        {{-- Actions --}}
                        <td class="rr-td" style="white-space:nowrap;">
                            <a :href="row.edit_url" class="rr-btn rr-btn-outline" style="padding:3px 8px;font-size:10px;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button @click="confirmDelete(row)"
                                    class="rr-btn rr-btn-danger" style="padding:3px 8px;font-size:10px;margin-left:3px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>
                <template x-if="filteredRows.length === 0">
                    <tr>
                        <td colspan="30" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">
                            <i class="fas fa-shield-alt text-slate-300 text-3xl mb-2 block"></i>
                            No risks found. Click <strong>Add Risk</strong> to create the first entry.
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Record count --}}
    <div style="font-size:11px;color:#94a3b8;padding:6px 2px;">
        Showing <strong x-text="filteredRows.length"></strong> of <strong x-text="rows.length"></strong> risks
    </div>

    {{-- ================================================================ --}}
    {{-- ADD / QUICK-CREATE MODAL                                          --}}
    {{-- ================================================================ --}}
    <div x-show="showModal" class="rr-modal-overlay" @keydown.escape.window="showModal=false" x-cloak>
        <div class="rr-modal" @click.away="showModal=false">
            <div class="rr-modal-header">
                <div class="flex items-center gap-3">
                    <i class="fas fa-shield-alt text-sky-400"></i>
                    <div>
                        <h2 style="font-size:14px;font-weight:800;margin:0;" x-text="editMode ? 'Edit Risk Entry' : 'Add New Risk Entry'"></h2>
                        <p style="font-size:10px;color:#94a3b8;margin:0;">All scores are auto-calculated</p>
                    </div>
                </div>
                <button @click="showModal=false" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:18px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="rr-modal-body">
                <form @submit.prevent="submitRisk()">

                    <div class="form-section-title"><i class="fas fa-info-circle mr-1"></i>General Information</div>
                    <div class="rr-form-grid">
                        <div>
                            <label class="form-label-sm">Risk Name *</label>
                            <input type="text" x-model="form.risk_name" class="form-input-sm" required>
                        </div>
                        <div>
                            <label class="form-label-sm">Risk Owner *</label>
                            <input type="text" x-model="form.risk_owner" class="form-input-sm" required>
                        </div>
                        <div>
                            <label class="form-label-sm">Department *</label>
                            <select x-model="form.department" class="form-input-sm" required>
                                <option value="">-- Select --</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->name }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label-sm">Date Identified *</label>
                            <input type="date" x-model="form.date_identified" class="form-input-sm" required>
                        </div>
                        <div>
                            <label class="form-label-sm">Category</label>
                            <select x-model="form.risk_category_id" class="form-input-sm">
                                <option value="">-- Select --</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label-sm">Asset ID Reference</label>
                            <input type="text" x-model="form.asset_id_ref" class="form-input-sm" placeholder="ASSET-001">
                        </div>
                        <div class="rr-form-full">
                            <label class="form-label-sm">Risk Description *</label>
                            <textarea x-model="form.risk_description" class="form-input-sm" rows="2" required></textarea>
                        </div>
                    </div>

                    <div class="form-section-title"><i class="fas fa-chart-bar mr-1"></i>Threat Analysis</div>
                    <div class="rr-form-grid-3">
                        <div>
                            <label class="form-label-sm">Threat Score (1–5)</label>
                            <input type="number" x-model.number="form.threat_score" min="1" max="5" class="form-input-sm">
                        </div>
                        <div>
                            <label class="form-label-sm">Confidentiality (1–5)</label>
                            <input type="number" x-model.number="form.confidentiality" min="1" max="5" class="form-input-sm">
                        </div>
                        <div>
                            <label class="form-label-sm">Integrity (1–5)</label>
                            <input type="number" x-model.number="form.integrity" min="1" max="5" class="form-input-sm">
                        </div>
                        <div>
                            <label class="form-label-sm">Availability (1–5)</label>
                            <input type="number" x-model.number="form.availability" min="1" max="5" class="form-input-sm">
                        </div>
                        <div class="rr-form-full">
                            <label class="form-label-sm">Existing Controls</label>
                            <textarea x-model="form.existing_controls" class="form-input-sm" rows="1"></textarea>
                        </div>
                    </div>

                    <div class="form-section-title" style="color:#1d4ed8;border-color:#bfdbfe;">
                        <i class="fas fa-calculator mr-1"></i>Inherent Risk Assessment
                    </div>
                    <div class="rr-form-grid-3">
                        <div>
                            <label class="form-label-sm">Likelihood (1–5) *</label>
                            <input type="number" x-model.number="form.likelihood" @input="recalc()"
                                   min="1" max="5" class="form-input-sm" required>
                            <div style="font-size:9px;color:#6b7280;margin-top:2px;">
                                1=Very Unlikely · 5=Frequent
                            </div>
                        </div>
                        <div>
                            <label class="form-label-sm">Impact (1–5) *</label>
                            <input type="number" x-model.number="form.impact" @input="recalc()"
                                   min="1" max="5" class="form-input-sm" required>
                            <div style="font-size:9px;color:#6b7280;margin-top:2px;">
                                1=Very Low · 5=Critical
                            </div>
                        </div>
                        <div>
                            <label class="form-label-sm">Inherent Score</label>
                            <div class="score-display" :class="'score-' + inherentLevel.toLowerCase()"
                                 x-text="inherentScore + ' — ' + inherentLevel"></div>
                        </div>
                    </div>

                    <div class="form-section-title" style="color:#7c3aed;border-color:#ddd6fe;">
                        <i class="fas fa-tools mr-1"></i>Treatment Plan
                    </div>
                    <div class="rr-form-grid">
                        <div>
                            <label class="form-label-sm">Treatment Decision *</label>
                            <select x-model="form.treatment_decision" class="form-input-sm" required>
                                @foreach(\App\Modules\RiskManagement\Models\RiskRegister::TREATMENT_DECISIONS as $td)
                                    <option value="{{ $td }}">{{ $td }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label-sm">Communication Status</label>
                            <select x-model="form.communication_status" class="form-input-sm">
                                <option value="">-- Select --</option>
                                <option value="Communicated">Communicated</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label-sm">Target Date</label>
                            <input type="date" x-model="form.target_date" class="form-input-sm">
                        </div>
                        <div>
                            <label class="form-label-sm">Review Date</label>
                            <input type="date" x-model="form.next_review_date" class="form-input-sm">
                        </div>
                        <div>
                            <label class="form-label-sm">Workflow Status</label>
                            <select x-model="form.status" class="form-input-sm">
                                @foreach(\App\Modules\RiskManagement\Models\RiskRegister::STATUSES as $st)
                                    <option value="{{ $st }}">{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label-sm">Framework Control</label>
                            <select x-model="form.framework_control_id" class="form-input-sm">
                                <option value="">-- None --</option>
                                @foreach($controls as $fc)
                                    <option value="{{ $fc->id }}">{{ $fc->control_id }} — {{ Str::limit($fc->requirement_description, 40) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rr-form-full">
                            <label class="form-label-sm">Recommended Control</label>
                            <textarea x-model="form.recommended_control" class="form-input-sm" rows="1"></textarea>
                        </div>
                    </div>

                    <div class="form-section-title" style="color:#065f46;border-color:#6ee7b7;">
                        <i class="fas fa-check-circle mr-1"></i>Residual Risk Assessment
                    </div>
                    <div class="rr-form-grid-3">
                        <div>
                            <label class="form-label-sm">Residual Likelihood (1–5) *</label>
                            <input type="number" x-model.number="form.residual_likelihood" @input="recalc()"
                                   min="1" max="5" class="form-input-sm" required>
                        </div>
                        <div>
                            <label class="form-label-sm">Residual Impact (1–5) *</label>
                            <input type="number" x-model.number="form.residual_impact" @input="recalc()"
                                   min="1" max="5" class="form-input-sm" required>
                        </div>
                        <div>
                            <label class="form-label-sm">Residual Score</label>
                            <div class="score-display" :class="'score-' + residualLevel.toLowerCase()"
                                 x-text="residualScore + ' — ' + residualLevel"></div>
                        </div>
                        <div class="rr-form-full">
                            <label class="form-label-sm">Follow-up Notes</label>
                            <textarea x-model="form.follow_up_notes" class="form-input-sm" rows="2"></textarea>
                        </div>
                    </div>

                    {{-- Control Effectiveness preview --}}
                    <div style="background:#f8fafc;border-radius:8px;padding:12px;margin-top:12px;display:flex;align-items:center;gap:20px;">
                        <div style="text-align:center;">
                            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Inherent</div>
                            <div style="font-size:20px;font-weight:900;" :class="'score-' + inherentLevel.toLowerCase()"
                                 style="border-radius:6px;padding:2px 10px;" x-text="inherentScore"></div>
                        </div>
                        <div style="font-size:20px;color:#94a3b8;">→</div>
                        <div style="text-align:center;">
                            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Residual</div>
                            <div style="font-size:20px;font-weight:900;" :class="'score-' + residualLevel.toLowerCase()"
                                 style="border-radius:6px;padding:2px 10px;" x-text="residualScore"></div>
                        </div>
                        <div style="flex:1;text-align:center;">
                            <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Risk Reduction</div>
                            <div style="font-size:20px;font-weight:900;color:#059669;" x-text="controlEff + '%'"></div>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0;">
                        <button type="button" @click="showModal=false" class="rr-btn rr-btn-outline">Cancel</button>
                        <button type="submit" class="rr-btn rr-btn-primary" :disabled="saving">
                            <i class="fas fa-save"></i>
                            <span x-text="saving ? 'Saving…' : (editMode ? 'Update Risk' : 'Create Risk')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div x-show="showDeleteConfirm" class="rr-modal-overlay" x-cloak>
        <div class="rr-modal" style="max-width:420px;" @click.away="showDeleteConfirm=false">
            <div class="rr-modal-header" style="background:linear-gradient(135deg,#7f1d1d,#dc2626);">
                <h2 style="font-size:14px;font-weight:800;margin:0;"><i class="fas fa-trash mr-2"></i>Delete Risk</h2>
            </div>
            <div class="rr-modal-body" style="text-align:center;padding:30px 20px;">
                <p style="font-size:14px;color:#374151;margin-bottom:6px;">Are you sure you want to delete</p>
                <p style="font-size:16px;font-weight:800;color:#dc2626;" x-text="'Risk ' + (deleteTarget?.risk_id ?? '')"></p>
                <p style="font-size:12px;color:#94a3b8;">This action cannot be undone.</p>
                <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
                    <button @click="showDeleteConfirm=false" class="rr-btn rr-btn-outline">Cancel</button>
                    <button @click="doDelete()" class="rr-btn rr-btn-danger"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast" class="rr-toast" x-cloak>
        <i class="fas fa-check-circle text-green-400"></i>
        <span x-text="toastMsg"></span>
    </div>

</div>
@endsection

@push('scripts')
<script>
function riskRegister() {
    return {
        // ── Data ──
        rows: {!! json_encode($entries->map(function($r) use ($project) {
            return [
                'id'                  => $r->id,
                'risk_id'             => $r->risk_id,
                'risk_name'           => $r->risk_name,
                'department'          => $r->department,
                'risk_owner'          => $r->risk_owner,
                'date_identified'     => $r->date_identified?->format('Y-m-d'),
                'asset_id_ref'        => $r->asset_id_ref,
                'category_name'       => $r->category,
                'risk_description'    => $r->risk_description,
                'threat_score'        => $r->threat_score,
                'confidentiality'     => $r->confidentiality,
                'integrity'           => $r->integrity,
                'availability'        => $r->availability,
                'existing_controls'   => $r->existing_controls,
                'likelihood'          => $r->likelihood,
                'impact'              => $r->impact,
                'inherent_score'      => $r->inherent_score,
                'inherent_risk_level' => $r->inherent_risk_level,
                'recommended_control' => $r->recommended_control,
                'treatment_decision'  => $r->treatment_decision,
                'communication_status'=> $r->communication_status,
                'status'              => $r->status,
                'target_date'         => $r->target_date?->format('Y-m-d'),
                'next_review_date'    => $r->next_review_date?->format('Y-m-d'),
                'residual_likelihood' => $r->residual_likelihood,
                'residual_impact'     => $r->residual_impact,
                'residual_score'      => $r->residual_score,
                'residual_risk_level' => $r->residual_risk_level,
                'follow_up_notes'     => $r->follow_up_notes,
                'edit_url'            => route('risk-register.edit', [$project, $r]),
            ];
        })) !!},

        kpis: @json($kpis),

        // ── Filters ──
        search: '',
        filterLevel: '',
        filterStatus: '',
        filterDept: '',
        filterTreatment: '',
        sortKey: 'inherent_score',
        sortAsc: false,

        // ── Modal state ──
        showModal: false,
        editMode: false,
        saving: false,
        showDeleteConfirm: false,
        deleteTarget: null,
        toast: false,
        toastMsg: '',

        // ── Live score state ──
        inherentScore: 1,
        residualScore: 1,
        inherentLevel: 'Low',
        residualLevel: 'Low',
        controlEff: 0,

        // ── Form ──
        form: {
            risk_name: '', risk_owner: '', department: '',
            date_identified: new Date().toISOString().slice(0,10),
            asset_id_ref: '', risk_category_id: '',
            risk_description: '', threat_score: 3,
            confidentiality: 3, integrity: 3, availability: 3,
            existing_controls: '',
            likelihood: 3, impact: 3,
            recommended_control: '',
            treatment_decision: 'In Review',
            communication_status: 'Pending',
            status: 'Draft',
            framework_control_id: '',
            residual_likelihood: 2, residual_impact: 2,
            target_date: '', next_review_date: '',
            follow_up_notes: '',
        },

        editId: null,

        // ── Lifecycle ──
        init() {
            this.recalc();
        },

        // ── Filtering + sorting ──
        get filteredRows() {
            let data = [...this.rows];
            const q  = this.search.toLowerCase();

            if (q) {
                data = data.filter(r =>
                    (r.risk_id + r.risk_name + r.risk_description + r.department + r.risk_owner)
                        .toLowerCase().includes(q)
                );
            }
            if (this.filterLevel)     data = data.filter(r => r.inherent_risk_level === this.filterLevel);
            if (this.filterStatus)    data = data.filter(r => r.status === this.filterStatus);
            if (this.filterDept)      data = data.filter(r => r.department === this.filterDept);
            if (this.filterTreatment) data = data.filter(r => r.treatment_decision === this.filterTreatment);

            const key = this.sortKey;
            data.sort((a, b) => {
                const av = a[key] ?? ''; const bv = b[key] ?? '';
                return this.sortAsc
                    ? (av < bv ? -1 : av > bv ? 1 : 0)
                    : (av > bv ? -1 : av < bv ? 1 : 0);
            });
            return data;
        },

        sort(key) {
            if (this.sortKey === key) { this.sortAsc = !this.sortAsc; } else { this.sortKey = key; this.sortAsc = false; }
        },

        // ── Score helpers ──
        recalc() {
            const l  = parseInt(this.form.likelihood)          || 1;
            const i  = parseInt(this.form.impact)              || 1;
            const rl = parseInt(this.form.residual_likelihood) || 1;
            const ri = parseInt(this.form.residual_impact)     || 1;

            this.inherentScore = l * i;
            this.residualScore = rl * ri;
            this.inherentLevel = this.scoreToLevel(this.inherentScore);
            this.residualLevel = this.scoreToLevel(this.residualScore);
            this.controlEff    = this.inherentScore > 0
                ? Math.round((1 - this.residualScore / this.inherentScore) * 100 * 10) / 10
                : 0;
        },

        scoreToLevel(s) {
            if (s >= 20) return 'Critical';
            if (s >= 12) return 'High';
            if (s >= 6)  return 'Medium';
            return 'Low';
        },

        scoreCssClass(score) {
            return 'score-' + this.scoreToLevel(score).toLowerCase();
        },

        likelihoodLabel(v) {
            return {1:'Very Unlikely',2:'Unlikely',3:'Possible',4:'Likely',5:'Frequent'}[v] ?? v;
        },
        impactLabel(v) {
            return {1:'Very Low',2:'Low',3:'Medium',4:'High',5:'Critical'}[v] ?? v;
        },

        treatmentCss(decision) {
            if (decision === 'Accepted') return 'treat-accepted';
            if (decision === 'Not Accepted') return 'treat-not-accepted';
            return 'treat-review';
        },

        statusCss(status) {
            const map = {
                'Draft':'status-draft', 'Open':'status-open',
                'Mitigating':'status-mitigating', 'Mitigated':'status-accepted',
                'Accepted':'status-accepted', 'Closed':'status-closed',
            };
            return map[status] ?? 'status-open';
        },

        // ── Modal ──
        openAddModal() {
            this.editMode = false;
            this.editId   = null;
            this.form = {
                risk_name:'', risk_owner:'', department:'',
                date_identified: new Date().toISOString().slice(0,10),
                asset_id_ref:'', risk_category_id:'',
                risk_description:'', threat_score:3,
                confidentiality:3, integrity:3, availability:3,
                existing_controls:'',
                likelihood:3, impact:3,
                recommended_control:'',
                treatment_decision:'In Review',
                communication_status:'Pending',
                status:'Draft',
                framework_control_id:'',
                residual_likelihood:2, residual_impact:2,
                target_date:'', next_review_date:'',
                follow_up_notes:'',
            };
            this.recalc();
            this.showModal = true;
        },

        // ── CRUD ──
        async submitRisk() {
            this.saving = true;
            const url    = this.editId
                ? `/projects/{{ $project->id }}/risk-register/${this.editId}`
                : `/projects/{{ $project->id }}/risk-register`;
            const method = this.editId ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();

                if (data.success) {
                    // Refresh rows
                    const r = data.risk;
                    const row = {
                        id:                  r.id,
                        risk_id:             r.risk_id,
                        risk_name:           r.risk_name,
                        department:          r.department,
                        risk_owner:          r.risk_owner,
                        date_identified:     r.date_identified,
                        asset_id_ref:        r.asset_id_ref,
                        category_name:       r.category ?? null,
                        risk_description:    r.risk_description,
                        threat_score:        r.threat_score,
                        confidentiality:     r.confidentiality,
                        integrity:           r.integrity,
                        availability:        r.availability,
                        existing_controls:   r.existing_controls,
                        likelihood:          r.likelihood,
                        impact:              r.impact,
                        inherent_score:      r.inherent_score,
                        inherent_risk_level: r.inherent_risk_level,
                        recommended_control: r.recommended_control,
                        treatment_decision:  r.treatment_decision,
                        communication_status:r.communication_status,
                        status:              r.status,
                        target_date:         r.target_date,
                        next_review_date:    r.next_review_date,
                        residual_likelihood: r.residual_likelihood,
                        residual_impact:     r.residual_impact,
                        residual_score:      r.residual_score,
                        residual_risk_level: r.residual_risk_level,
                        follow_up_notes:     r.follow_up_notes,
                        edit_url:            `/projects/{{ $project->id }}/risk-register/${r.id}/edit`,
                    };

                    if (this.editId) {
                        const idx = this.rows.findIndex(x => x.id === this.editId);
                        if (idx >= 0) this.rows[idx] = row;
                    } else {
                        this.rows.unshift(row);
                    }

                    this.kpis = data.kpis;
                    this.showModal = false;
                    this.showToast(this.editId ? 'Risk updated successfully.' : 'Risk created successfully.');
                } else {
                    alert(data.message ?? 'Failed to save risk.');
                }
            } catch (e) {
                alert('Network error. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(row) {
            this.deleteTarget = row;
            this.showDeleteConfirm = true;
        },

        async doDelete() {
            if (!this.deleteTarget) return;
            try {
                const res = await fetch(`/projects/{{ $project->id }}/risk-register/${this.deleteTarget.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.rows = this.rows.filter(r => r.id !== this.deleteTarget.id);
                    this.kpis = data.kpis;
                    this.showDeleteConfirm = false;
                    this.showToast('Risk deleted.');
                }
            } catch(e) {
                alert('Failed to delete risk.');
            }
        },

        showToast(msg) {
            this.toastMsg = msg;
            this.toast = true;
            setTimeout(() => this.toast = false, 3000);
        },
    };
}
</script>
@endpush
