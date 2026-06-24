@extends('layouts.app')

@push('styles')
<style>
/* ======================================================
   RISK HEAT MAP — Exact match of uploaded dual-panel image
   ====================================================== */
.hm-page { font-family: 'Inter', sans-serif; }

/* Page header */
.hm-page-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    gap: 10px; margin-bottom: 16px; text-align: center;
}
.hm-page-header h1 {
    font-size: 15px; font-weight: 900;
    letter-spacing: .06em; text-transform: uppercase; margin: 0;
}

/* KPI row */
.hm-kpi-row {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;
    margin-bottom: 20px;
}
.hm-kpi {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: 12px 14px;
}
.hm-kpi .label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.hm-kpi .value { font-size: 26px; font-weight: 900; color: #0f172a; margin-top: 2px; }
.hm-kpi.kpi-crit .value { color: #c0392b; }
.hm-kpi.kpi-high .value { color: #e67e22; }
.hm-kpi.kpi-med  .value { color: #d4a017; }
.hm-kpi.kpi-low  .value { color: #27ae60; }

/* Dual matrix panels wrapper */
.hm-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

/* Single matrix card */
.hm-card {
    background: #fff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.hm-card-header {
    padding: 10px 16px;
    text-align: center;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #fff;
}
.hm-card-header.inherent { background: linear-gradient(135deg, #7f1d1d, #c0392b); }
.hm-card-header.residual { background: linear-gradient(135deg, #064e3b, #059669); }

.hm-card-body { padding: 16px; }

/* Matrix table */
.hm-matrix-wrap { position: relative; }
.hm-matrix {
    border-collapse: collapse;
    width: 100%;
}

/* Y-axis label (vertical, left side) */
.hm-y-label {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .1em;
    color: #64748b;
    text-align: center;
    padding: 0 6px;
    white-space: nowrap;
}

/* Likelihood row header cells */
.hm-row-label {
    font-size: 9px; font-weight: 700; color: #374151;
    text-align: right; padding: 0 8px 0 4px;
    white-space: nowrap; background: #f8fafc;
    border: 1px solid #e2e8f0;
    min-width: 70px;
}

/* Impact column header cells */
.hm-col-label {
    font-size: 9px; font-weight: 700; color: #374151;
    text-align: center; padding: 4px 6px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
}

/* Matrix cells */
.hm-cell {
    width: 58px; height: 56px;
    border: 2px solid rgba(255,255,255,.4);
    text-align: center; vertical-align: middle;
    font-size: 16px; font-weight: 900;
    cursor: default;
    transition: transform .15s, box-shadow .15s;
    position: relative;
}
.hm-cell:hover {
    transform: scale(1.06);
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
    z-index: 2;
}
.hm-cell .score-label {
    font-size: 8px; font-weight: 700;
    position: absolute; bottom: 3px; right: 4px;
    opacity: .7;
}

/* Cell background colours — matching uploaded heatmap gradient */
/* Dark Red (Critical ≥20) */
.cell-cr { background: #7f1d1d; color: #fff; }
/* Red (High zone, score 16–19) */
.cell-r  { background: #c0392b; color: #fff; }
/* Salmon/Orange (High zone, 12–15) */
.cell-o  { background: #e67e22; color: #fff; }
/* Yellow (Medium, 6–11) */
.cell-y  { background: #f1c40f; color: #333; }
/* Light green (Low, 4–5) */
.cell-lg { background: #a3e635; color: #333; }
/* Green (Low, 1–3) */
.cell-g  { background: #22c55e; color: #fff; }
/* Very light / empty */
.cell-w  { background: #f0fdf4; color: #999; }

/* Count chip */
.hm-count {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 50%;
    background: rgba(255,255,255,.25);
    font-size: 13px; font-weight: 900;
}
.hm-cell.cell-y .hm-count,
.hm-cell.cell-lg .hm-count { background: rgba(0,0,0,.1); color: #333; }
.hm-cell.cell-w .hm-count  { background: rgba(0,0,0,.06); color: #999; }

/* Legend */
.hm-legend {
    display: flex; gap: 8px; flex-wrap: wrap;
    margin-top: 14px; padding-top: 14px;
    border-top: 1px solid #f1f5f9;
}
.hm-legend-item {
    display: flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 700; color: #374151;
}
.hm-legend-swatch {
    width: 18px; height: 18px; border-radius: 4px;
}

/* X-axis label */
.hm-x-label {
    text-align: center; font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .1em;
    color: #64748b; padding: 6px 0 0;
}
</style>
@endpush

@section('content')
<div class="hm-page">

    {{-- ── PAGE HEADER ── --}}
    <div class="hm-page-header">
        <i class="fas fa-fire text-orange-400 text-xl"></i>
        <h1>Risk Heat Map &mdash; Inherent &amp; Residual Risk Matrix</h1>
        <i class="fas fa-fire text-orange-400 text-xl"></i>
    </div>

    {{-- ── KPIs ── --}}
    <div class="hm-kpi-row">
        <div class="hm-kpi kpi-crit">
            <div class="label">Critical Risks</div>
            <div class="value">{{ $kpis['critical'] }}</div>
        </div>
        <div class="hm-kpi kpi-high">
            <div class="label">High Risks</div>
            <div class="value">{{ $kpis['high'] }}</div>
        </div>
        <div class="hm-kpi kpi-med">
            <div class="label">Medium Risks</div>
            <div class="value">{{ $kpis['medium'] }}</div>
        </div>
        <div class="hm-kpi kpi-low">
            <div class="label">Low Risks</div>
            <div class="value">{{ $kpis['low'] }}</div>
        </div>
        <div class="hm-kpi">
            <div class="label">Control Effectiveness</div>
            <div class="value">{{ $kpis['controlEff'] }}%</div>
        </div>
    </div>

    {{-- ── DUAL MATRIX PANELS ── --}}
    <div class="hm-panels">

        @foreach(['inherent' => 'INHERENT RISK REGISTER HEAT MAP', 'residual' => 'RESIDUAL RISK REGISTER HEAT MAP'] as $type => $title)
        @php
            $cells = $type === 'inherent' ? $inherentCells : $residualCells;
            // Build a keyed lookup: [likelihood][impact] => cell
            $cellMap = [];
            foreach ($cells as $c) {
                $cellMap[$c['likelihood']][$c['impact']] = $c;
            }
        @endphp

        <div class="hm-card">
            <div class="hm-card-header {{ $type }}">
                <i class="fas {{ $type === 'inherent' ? 'fa-bolt' : 'fa-shield-alt' }} mr-2"></i>
                {{ $title }}
            </div>
            <div class="hm-card-body">
                <div style="display:flex;gap:4px;align-items:flex-start;">

                    {{-- Y-axis label --}}
                    <div class="hm-y-label" style="height:{{ count($likelihoodAxis) * 60 }}px;">
                        ← Likelihood Level →
                    </div>

                    {{-- Matrix table --}}
                    <div class="hm-matrix-wrap" style="flex:1;">
                        <table class="hm-matrix">
                            <thead>
                                <tr>
                                    <th class="hm-row-label" style="background:transparent;border:none;"></th>
                                    @foreach($impactAxis as $iVal => $iLabel)
                                        <th class="hm-col-label">{{ $iLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($likelihoodAxis as $lVal => $lLabel)
                                    <tr>
                                        <td class="hm-row-label">{{ $lLabel }}</td>
                                        @foreach($impactAxis as $iVal => $iLabel)
                                            @php
                                                $cell  = $cellMap[$lVal][$iVal] ?? ['count'=>0,'score'=>$lVal*$iVal,'level'=>'Low'];
                                                $score = $cell['score'];
                                                $count = $cell['count'];
                                                // Determine background class based on score
                                                if      ($score >= 20) $cls = 'cell-cr';
                                                elseif  ($score >= 16) $cls = 'cell-r';
                                                elseif  ($score >= 12) $cls = 'cell-o';
                                                elseif  ($score >= 6)  $cls = 'cell-y';
                                                elseif  ($score >= 4)  $cls = 'cell-lg';
                                                elseif  ($score >= 2)  $cls = 'cell-g';
                                                else                   $cls = 'cell-w';
                                            @endphp
                                            <td class="hm-cell {{ $cls }}"
                                                title="Likelihood: {{ $lLabel }} | Impact: {{ $iLabel }} | Score: {{ $score }} | Risks: {{ $count }}">
                                                <div class="hm-count">{{ $count > 0 ? $count : '' }}</div>
                                                <span class="score-label">{{ $score }}</span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- X-axis label --}}
                        <div class="hm-x-label">← Impact Level →</div>
                    </div>
                </div>

                {{-- ── LEGEND ── --}}
                <div class="hm-legend">
                    <div class="hm-legend-item">
                        <div class="hm-legend-swatch" style="background:#7f1d1d;"></div>
                        Critical ≥ 20
                    </div>
                    <div class="hm-legend-item">
                        <div class="hm-legend-swatch" style="background:#c0392b;"></div>
                        High 16–19
                    </div>
                    <div class="hm-legend-item">
                        <div class="hm-legend-swatch" style="background:#e67e22;"></div>
                        High 12–15
                    </div>
                    <div class="hm-legend-item">
                        <div class="hm-legend-swatch" style="background:#f1c40f;"></div>
                        Medium 6–11
                    </div>
                    <div class="hm-legend-item">
                        <div class="hm-legend-swatch" style="background:#a3e635;"></div>
                        Low 4–5
                    </div>
                    <div class="hm-legend-item">
                        <div class="hm-legend-swatch" style="background:#22c55e;"></div>
                        Low 1–3
                    </div>
                </div>
            </div>
        </div>
        @endforeach

    </div>{{-- /.hm-panels --}}

    {{-- ── SUMMARY TABLE ── --}}
    <div style="margin-top:24px;background:#fff;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden;">
        <div style="padding:10px 16px;background:#1e293b;color:#fff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;">
            <i class="fas fa-table mr-2"></i>Heat Map Summary
        </div>
        <div style="padding:16px;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:8px 12px;text-align:left;border-bottom:2px solid #e2e8f0;font-weight:700;font-size:10px;text-transform:uppercase;">Level</th>
                        <th style="padding:8px 12px;text-align:center;border-bottom:2px solid #e2e8f0;font-weight:700;font-size:10px;text-transform:uppercase;">Score Range</th>
                        <th style="padding:8px 12px;text-align:center;border-bottom:2px solid #e2e8f0;font-weight:700;font-size:10px;text-transform:uppercase;">Inherent Count</th>
                        <th style="padding:8px 12px;text-align:center;border-bottom:2px solid #e2e8f0;font-weight:700;font-size:10px;text-transform:uppercase;">Residual Count</th>
                        <th style="padding:8px 12px;text-align:center;border-bottom:2px solid #e2e8f0;font-weight:700;font-size:10px;text-transform:uppercase;">Reduction</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $levels = ['Critical','High','Medium','Low'];
                        $scoreRanges = ['≥ 20','12 – 19','6 – 11','1 – 5'];
                        $inherentCounts = ['critical' => $kpis['critical'], 'high' => $kpis['high'], 'medium' => $kpis['medium'], 'low' => $kpis['low']];
                        // Residual counts from cells
                        $resLevels = ['Critical'=>0,'High'=>0,'Medium'=>0,'Low'=>0];
                        foreach($residualCells as $c) { $resLevels[$c['level']] += $c['count']; }
                        $colors = ['Critical'=>'#c0392b','High'=>'#e67e22','Medium'=>'#d4a017','Low'=>'#27ae60'];
                    @endphp
                    @foreach($levels as $i => $level)
                    @php
                        $inh = $inherentCounts[strtolower($level)];
                        $res = $resLevels[$level];
                        $red = $inh > 0 ? round((1 - $res/$inh) * 100) : 0;
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 12px;">
                            <span style="background:{{ $colors[$level] }};color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:4px;">
                                {{ $level }}
                            </span>
                        </td>
                        <td style="text-align:center;padding:8px 12px;font-weight:600;">{{ $scoreRanges[$i] }}</td>
                        <td style="text-align:center;padding:8px 12px;font-size:16px;font-weight:900;color:{{ $colors[$level] }};">{{ $inh }}</td>
                        <td style="text-align:center;padding:8px 12px;font-size:16px;font-weight:900;color:{{ $colors[$level] }};">{{ $res }}</td>
                        <td style="text-align:center;padding:8px 12px;">
                            @if($inh > 0)
                                <div style="background:#f0fdf4;color:#166534;font-weight:800;padding:3px 10px;border-radius:4px;display:inline-block;">
                                    {{ $red }}% ↓
                                </div>
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Navigation buttons --}}
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end;">
        <a href="{{ route('risk-register.index', $project) }}"
           style="display:inline-flex;align-items:center;gap:6px;background:#1e293b;color:#fff;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
            <i class="fas fa-list"></i> Back to Risk Register
        </a>
        <a href="{{ route('risk-register.export-pdf', $project) }}"
           style="display:inline-flex;align-items:center;gap:6px;background:#d97706;color:#fff;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;" target="_blank">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>

</div>
@endsection
