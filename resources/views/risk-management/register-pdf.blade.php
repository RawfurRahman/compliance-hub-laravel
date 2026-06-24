<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Risk Register - {{ $project->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #0f172a;
            text-transform: uppercase;
        }
        .header p {
            margin: 4px 0 0 0;
            color: #64748b;
            font-size: 11px;
        }
        
        /* KPI Dashboard Grid */
        .kpi-container {
            margin-bottom: 20px;
            width: 100%;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .kpi-cell {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            background: #f8fafc;
            text-align: center;
        }
        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-critical { color: #dc2626; }
        .kpi-high { color: #ea580c; }
        .kpi-medium { color: #d97706; }
        .kpi-low { color: #16a34a; }

        /* Main Table */
        .risk-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .risk-table th {
            font-weight: bold;
            background-color: #0f172a;
            color: #ffffff;
            border: 1px solid #334155;
            padding: 6px 4px;
            text-align: center;
            font-size: 8px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .risk-table td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            font-size: 8px;
            vertical-align: middle;
        }
        .risk-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            text-align: center;
        }
        .badge-critical { background-color: #fee2e2; color: #991b1b; }
        .badge-high { background-color: #ffedd5; color: #c2410c; }
        .badge-medium { background-color: #fef9c3; color: #854d0e; }
        .badge-low { background-color: #dcfce7; color: #166534; }
        
        .badge-accepted { background-color: #dcfce7; color: #166534; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; }
        .badge-review { background-color: #fef3c7; color: #92400e; }

        .score-critical { background-color: #ef4444; color: #ffffff; font-weight: bold; text-align: center; }
        .score-high { background-color: #f97316; color: #ffffff; font-weight: bold; text-align: center; }
        .score-medium { background-color: #facc15; color: #334155; font-weight: bold; text-align: center; }
        .score-low { background-color: #4ade80; color: #ffffff; font-weight: bold; text-align: center; }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: right;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Integrated Risk Register Report</h1>
        <p>Project: <strong>{{ $project->name }}</strong> &mdash; Generated on {{ now()->format('F d, Y H:i') }}</p>
    </div>

    {{-- KPIs Summary --}}
    <div class="kpi-container">
        <table class="kpi-table">
            <tr>
                <td class="kpi-cell">
                    <div class="kpi-label">Total Risks</div>
                    <div class="kpi-value">{{ $kpis['total'] }}</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Critical Risks</div>
                    <div class="kpi-value kpi-critical">{{ $kpis['critical'] }}</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">High Risks</div>
                    <div class="kpi-value kpi-high">{{ $kpis['high'] }}</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Medium Risks</div>
                    <div class="kpi-value kpi-medium">{{ $kpis['medium'] }}</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Low Risks</div>
                    <div class="kpi-value kpi-low">{{ $kpis['low'] }}</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Open Gaps</div>
                    <div class="kpi-value kpi-critical">{{ $kpis['open'] }}</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Control Effectiveness</div>
                    <div class="kpi-value">{{ $kpis['controlEff'] }}%</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Main Register --}}
    <table class="risk-table">
        <thead>
            <tr>
                <th style="width: 2%;">#</th>
                <th style="width: 6%;">Risk ID</th>
                <th style="width: 14%;">Risk Title</th>
                <th style="width: 8%;">Department</th>
                <th style="width: 8%;">Owner</th>
                <th style="width: 6%;">Identify Date</th>
                <th style="width: 6%;">Asset ID</th>
                <th style="width: 10%;">Description</th>
                <th style="width: 3%;">L</th>
                <th style="width: 3%;">I</th>
                <th style="width: 4%;">Inherent</th>
                <th style="width: 6%;">Recommended Control</th>
                <th style="width: 6%;">Treatment</th>
                <th style="width: 6%;">Status</th>
                <th style="width: 3%;">R.L</th>
                <th style="width: 3%;">R.I</th>
                <th style="width: 4%;">Residual</th>
                <th style="width: 7%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $index => $r)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold; text-align: center;">{{ $r->risk_id }}</td>
                    <td style="font-weight: bold;">{{ $r->risk_name }}</td>
                    <td>{{ $r->department }}</td>
                    <td>{{ $r->risk_owner }}</td>
                    <td style="text-align: center;">{{ $r->date_identified?->format('Y-m-d') }}</td>
                    <td style="text-align: center;">{{ $r->asset_id_ref ?? '—' }}</td>
                    <td>{{ Str::limit($r->risk_description, 100) }}</td>
                    <td style="text-align: center;">{{ $r->likelihood }}</td>
                    <td style="text-align: center;">{{ $r->impact }}</td>
                    <td class="{{ $r->inherent_risk_level === 'Critical' ? 'score-critical' : ($r->inherent_risk_level === 'High' ? 'score-high' : ($r->inherent_risk_level === 'Medium' ? 'score-medium' : 'score-low')) }}">
                        {{ $r->inherent_score }} &mdash; {{ $r->inherent_risk_level }}
                    </td>
                    <td>{{ Str::limit($r->recommended_control, 80) }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ $r->treatment_decision === 'Accepted' ? 'badge-accepted' : ($r->treatment_decision === 'Not Accepted' ? 'badge-rejected' : 'badge-review') }}">
                            {{ $r->treatment_decision }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge" style="background-color: #cbd5e1; color: #1e293b;">
                            {{ $r->status }}
                        </span>
                    </td>
                    <td style="text-align: center;">{{ $r->residual_likelihood }}</td>
                    <td style="text-align: center;">{{ $r->residual_impact }}</td>
                    <td class="{{ $r->residual_risk_level === 'Critical' ? 'score-critical' : ($r->residual_risk_level === 'High' ? 'score-high' : ($r->residual_risk_level === 'Medium' ? 'score-medium' : 'score-low')) }}">
                        {{ $r->residual_score }} &mdash; {{ $r->residual_risk_level }}
                    </td>
                    <td>{{ Str::limit($r->follow_up_notes, 80) }}</td>
                </tr>
            @endforeach
            @if($entries->isEmpty())
                <tr>
                    <td colspan="18" style="text-align: center; padding: 20px; color: #64748b;">No risks recorded.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        ComplianceHub Cybersecurity GRC Platform &mdash; Confidential Document
    </div>

</body>
</html>
