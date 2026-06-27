@extends('layouts.app')

@push('styles')
<style>
/* Enterprise Risks — reuses .rr-* classes from register.blade.php */
.er-container { width: 100%; overflow: hidden; font-family: 'Inter', sans-serif; }
.er-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    color: #fff; text-align: center; padding: 12px 24px;
    border-radius: 8px 8px 0 0;
    display: flex; align-items: center; justify-content: center; gap: 10px;
}
.er-header h1 { font-size: 15px; font-weight: 800; letter-spacing: 0.05em; margin: 0; text-transform: uppercase; }
.er-kpi-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin: 12px 0; }
.er-kpi { background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 12px; }
.er-kpi .label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.er-kpi .value { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px; }
.er-kpi.critical .value { color: #c0392b; }
.er-kpi.high     .value { color: #e67e22; }
.er-kpi.medium   .value { color: #d4a017; }
.er-kpi.low      .value { color: #27ae60; }
.er-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0; gap: 10px; flex-wrap: wrap;
}
</style>
@endpush

@section('content')
<div class="er-container">

    <div class="er-header">
        <i class="fas fa-building text-sky-400 text-xl"></i>
        <h1>Enterprise Risks</h1>
        <i class="fas fa-building text-sky-400 text-xl"></i>
    </div>

    {{-- KPIs --}}
    <div class="er-kpi-grid">
        <div class="er-kpi">
            <div class="label">Enterprise Risks</div>
            <div class="value">{{ $entries->count() }}</div>
        </div>
        <div class="er-kpi critical">
            <div class="label">Critical</div>
            <div class="value">{{ $entries->where('inherent_risk_level', 'Critical')->count() }}</div>
        </div>
        <div class="er-kpi high">
            <div class="label">High</div>
            <div class="value">{{ $entries->where('inherent_risk_level', 'High')->count() }}</div>
        </div>
        <div class="er-kpi medium">
            <div class="label">Medium</div>
            <div class="value">{{ $entries->where('inherent_risk_level', 'Medium')->count() }}</div>
        </div>
        <div class="er-kpi low">
            <div class="label">Low</div>
            <div class="value">{{ $entries->where('inherent_risk_level', 'Low')->count() }}</div>
        </div>
        <div class="er-kpi">
            <div class="label">Open</div>
            <div class="value">{{ $entries->where('lifecycle_status', '!=', 'closed')->count() }}</div>
        </div>
        <div class="er-kpi">
            <div class="label">Total Risks</div>
            <div class="value">{{ $kpis['total'] ?? 0 }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="er-toolbar">
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500">Showing only risks marked as enterprise risks.</span>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('risk-register.index', $project) }}" class="rr-btn rr-btn-outline">
                <i class="fas fa-list"></i> All Risks
            </a>
            <a href="{{ route('risk-register.heatmap', $project) }}" class="rr-btn rr-btn-info">
                <i class="fas fa-fire"></i> Heat Map
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="rr-table-wrap">
        <table class="rr-table">
            <thead>
                <tr>
                    <th class="rr-th" style="min-width:80px;">ID</th>
                    <th class="rr-th" style="min-width:200px;">Enterprise Risk Name</th>
                    <th class="rr-th" style="min-width:100px;">Owner</th>
                    <th class="rr-th" style="min-width:90px;">Inherent Risk</th>
                    <th class="rr-th" style="min-width:180px;">Treatment Plan</th>
                    <th class="rr-th" style="min-width:100px;">Treatment Status</th>
                    <th class="rr-th" style="min-width:90px;">Residual Risk</th>
                    <th class="rr-th" style="min-width:90px;">Status</th>
                    <th class="rr-th" style="min-width:90px;">Identified Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $risk)
                <tr class="rr-tr">
                    <td class="rr-td mono">{{ $risk->risk_id }}</td>
                    <td class="rr-td" style="font-weight:600;">
                        <a href="{{ route('risk-register.edit', [$project, $risk]) }}" class="text-indigo-600 hover:text-indigo-800 underline decoration-indigo-300">
                            {{ $risk->risk_name }}
                        </a>
                    </td>
                    <td class="rr-td">{{ $risk->risk_owner }}</td>
                    <td class="rr-td" style="text-align:center;">
                        <span class="risk-badge" style="{{ \App\Modules\RiskManagement\Models\RiskRegister::levelToBgStyle($risk->inherent_risk_level) }}">
                            {{ $risk->inherent_risk_level }}
                        </span>
                    </td>
                    <td class="rr-td" style="max-width:180px;white-space:normal;font-size:10px;">
                        {{ $risk->recommended_control ?? '—' }}
                    </td>
                    <td class="rr-td">
                        @php
                            $treatClass = $risk->treatment_decision === 'Accepted' ? 'treat-accepted'
                                        : ($risk->treatment_decision === 'Not Accepted' ? 'treat-not-accepted' : 'treat-review');
                        @endphp
                        <span class="risk-badge {{ $treatClass }}">
                            {{ $risk->treatment_decision ?? '—' }}
                        </span>
                    </td>
                    <td class="rr-td" style="text-align:center;">
                        <span class="risk-badge" style="{{ \App\Modules\RiskManagement\Models\RiskRegister::levelToBgStyle($risk->residual_risk_level) }}">
                            {{ $risk->residual_risk_level }}
                        </span>
                    </td>
                    <td class="rr-td">
                        @php
                            $lifecycleColors = [
                                'draft' => 'status-draft',
                                'assessed' => 'status-mitigating',
                                'accepted' => 'status-accepted',
                                'treated' => 'status-mitigating',
                                'monitoring' => 'status-mitigating',
                                'escalated' => 'status-open',
                                'closed' => 'status-closed',
                                'expired' => 'status-open',
                            ];
                            $lcClass = $lifecycleColors[$risk->lifecycle_status] ?? 'status-open';
                        @endphp
                        <span class="risk-badge {{ $lcClass }}">
                            {{ ucfirst($risk->lifecycle_status ?? 'draft') }}
                        </span>
                    </td>
                    <td class="rr-td">{{ $risk->date_identified?->format('Y-m-d') ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">
                        <i class="fas fa-building text-slate-300 text-3xl mb-2 block"></i>
                        No enterprise risks found. Mark a risk as <strong>Enterprise Risk</strong> to see it here.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="font-size:11px;color:#94a3b8;padding:6px 2px;">
        Showing <strong>{{ $entries->count() }}</strong> enterprise risk(s)
    </div>

</div>
@endsection
