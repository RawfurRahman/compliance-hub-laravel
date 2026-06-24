<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gap Assessment Report - {{ $project->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #1e293b;
            padding: 20px;
        }
        h1 { font-size: 22px; font-weight: 800; margin: 0 0 4px; color: #0f172a; }
        h2 { font-size: 14px; font-weight: 700; margin: 20px 0 10px; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; }
        h3 { font-size: 11px; font-weight: 700; margin: 12px 0 6px; color: #334155; }
        .subtitle { font-size: 9px; color: #64748b; margin-bottom: 16px; }
        .header { margin-bottom: 20px; }
        .stats-row { display: flex; gap: 12px; margin-bottom: 16px; }
        .stat-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; text-align: center; }
        .stat-value { font-size: 18px; font-weight: 800; }
        .stat-label { font-size: 7px; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; }
        .text-emerald { color: #059669; }
        .text-red { color: #dc2626; }
        .text-amber { color: #d97706; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 7px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 8px; }
        .risk-high { color: #dc2626; font-weight: 700; }
        .risk-medium { color: #d97706; font-weight: 700; }
        .risk-low { color: #059669; }
        .compliant { color: #059669; font-weight: 700; }
        .non-compliant { color: #dc2626; font-weight: 700; }
        .domain-section { margin-bottom: 20px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 7px; color: #94a3b8; text-align: center; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; }
        .summary-box table { margin-bottom: 0; }
        .summary-box th { background: transparent; border-bottom: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gap Assessment Report</h1>
        <div class="subtitle">
            <strong>{{ $project->name }}</strong> &mdash;
            {{ $framework?->name ?? 'N/A' }} &mdash;
            Generated: {{ now()->format('d M Y') }}
        </div>
    </div>

    {{-- Overall Stats --}}
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-value">{{ $overallStats['total'] }}</div>
            <div class="stat-label">Total Controls</div>
        </div>
        <div class="stat-box">
            <div class="stat-value text-emerald">{{ $overallStats['compliant'] }}</div>
            <div class="stat-label">Compliant</div>
        </div>
        <div class="stat-box">
            <div class="stat-value text-red">{{ $overallStats['high'] }}</div>
            <div class="stat-label">High Risk</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $overallStats['compliancePct'] }}%</div>
            <div class="stat-label">Compliance</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $overallStats['progressScore'] }}%</div>
            <div class="stat-label">Progress</div>
        </div>
    </div>

    {{-- Domain Breakdown --}}
    <h2>Domain Breakdown</h2>
    <div class="summary-box">
        <table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Total</th>
                    <th>Compliant</th>
                    <th>Compliance %</th>
                    <th>High Risk</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedFindings as $domain => $findings)
                @php
                    $total = $findings->count();
                    $compliant = $findings->where('is_compliant', true)->count();
                    $high = $findings->where('risk_rating', 'High')->count();
                    $cpct = $total > 0 ? round(($compliant / $total) * 100, 1) : 0;
                    $inProg = $findings->where('status', 'In Progress')->count();
                    $closed = $findings->where('status', 'Closed')->count();
                    $prog = $total > 0 ? round((($inProg * 0.5) + $closed) / $total * 100, 1) : 0;
                @endphp
                <tr>
                    <td><strong>{{ $domain }}</strong></td>
                    <td>{{ $total }}</td>
                    <td>{{ $compliant }}</td>
                    <td>{{ $cpct }}%</td>
                    <td>{{ $high }}</td>
                    <td>{{ $prog }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Detailed Findings by Domain --}}
    @foreach($groupedFindings as $domain => $findings)
    <div class="domain-section">
        <h2>{{ $domain }} ({{ $findings->count() }} controls)</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">Control</th>
                    <th>Requirement</th>
                    <th style="width: 60px;">Status</th>
                    <th style="width: 50px;">Risk</th>
                    <th style="width: 50px;">Compliant</th>
                    <th>Observation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($findings as $finding)
                <tr>
                    <td>{{ $finding->frameworkControl?->control_id ?? 'N/A' }}</td>
                    <td>{{ Str::limit($finding->frameworkControl?->requirement_description ?? '', 80) }}</td>
                    <td>{{ $finding->status }}</td>
                    <td class="risk-{{ strtolower($finding->risk_rating) }}">{{ $finding->risk_rating }}</td>
                    <td class="{{ $finding->is_compliant ? 'compliant' : 'non-compliant' }}">
                        {{ $finding->is_compliant ? 'Yes' : 'No' }}
                    </td>
                    <td>{{ Str::limit($finding->observation ?? '-', 60) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        ComplianceHub Gap Assessment Report &mdash; {{ $project->name }} &mdash; {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
