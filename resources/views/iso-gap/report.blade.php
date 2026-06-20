<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISO 27001:2022 Gap Assessment Report &ndash; {{ $project->name }}</title>
    <style>
        /* ------------------------------------------------------------------ */
        /* Reset & Base                                                        */
        /* ------------------------------------------------------------------ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            color: #1e293b;
            line-height: 1.55;
            background: #ffffff;
        }
        .page { padding: 36px 48px; }

        /* ------------------------------------------------------------------ */
        /* Report Header / Cover                                               */
        /* ------------------------------------------------------------------ */
        .report-header {
            text-align: center;
            padding: 36px 0 28px;
            border-bottom: 3px solid #1e3a5f;
            margin-bottom: 28px;
        }
        .report-header .org-label {
            font-size: 9pt;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
        }
        .report-header h1 {
            font-size: 20pt;
            font-weight: 800;
            color: #1e3a5f;
            margin: 10px 0 6px;
            line-height: 1.2;
        }
        .report-header .subtitle {
            font-size: 10.5pt;
            color: #475569;
        }
        .report-header .meta {
            margin-top: 14px;
            font-size: 8.5pt;
            color: #94a3b8;
        }
        .report-header .meta strong { color: #475569; }

        /* ------------------------------------------------------------------ */
        /* Section & Sub-section Headings                                      */
        /* ------------------------------------------------------------------ */
        .section-title {
            font-size: 12.5pt;
            font-weight: 700;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 5px;
            margin: 26px 0 12px;
        }
        .sub-title {
            font-size: 10.5pt;
            font-weight: 600;
            color: #334155;
            margin: 14px 0 6px;
        }
        p { margin-bottom: 7px; font-size: 9.5pt; color: #374151; }

        /* ------------------------------------------------------------------ */
        /* Tables                                                              */
        /* ------------------------------------------------------------------ */
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9pt; }
        thead th {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 7px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 8.5pt;
            letter-spacing: 0.3px;
        }
        tbody td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background-color: #f8fafc; }

        /* ------------------------------------------------------------------ */
        /* Badges                                                              */
        /* ------------------------------------------------------------------ */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .badge-high     { background: #fee2e2; color: #991b1b; }
        .badge-medium   { background: #fef3c7; color: #92400e; }
        .badge-low      { background: #d1fae5; color: #065f46; }
        .badge-open     { background: #fee2e2; color: #991b1b; }
        .badge-progress { background: #dbeafe; color: #1e40af; }
        .badge-closed   { background: #d1fae5; color: #065f46; }

        /* ------------------------------------------------------------------ */
        /* Finding Cards (Section 4)                                           */
        /* ------------------------------------------------------------------ */
        .finding-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .finding-card-header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 3px 3px 0 0;
        }
        .finding-card-header .fc-serial {
            font-size: 8pt;
            opacity: 0.75;
            margin-bottom: 2px;
        }
        .finding-card-header .fc-title {
            font-size: 10.5pt;
            font-weight: 700;
        }
        .finding-card-body table { margin: 0; }
        .finding-card-body td { border: 1px solid #e2e8f0; }
        .finding-card-body td.label-cell {
            width: 28%;
            font-weight: 600;
            font-size: 8.5pt;
            color: #374151;
            background-color: #f8fafc;
        }

        /* ------------------------------------------------------------------ */
        /* Page Utilities                                                      */
        /* ------------------------------------------------------------------ */
        .page-break { page-break-after: always; }
        .no-break   { page-break-inside: avoid; }

        /* ------------------------------------------------------------------ */
        /* Footer                                                              */
        /* ------------------------------------------------------------------ */
        .report-footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ================================================================== --}}
    {{-- COVER / HEADER                                                      --}}
    {{-- ================================================================== --}}
    <div class="report-header">
        <div class="org-label">Compliance Hub &mdash; Confidential</div>
        <h1>ISO 27001:2022 Gap Assessment Report</h1>
        <div class="subtitle">Information Security Management System (ISMS)</div>
        <div class="meta">
            Project: <strong>{{ $project->name }}</strong>
            &nbsp;&bull;&nbsp;
            Generated: <strong>{{ now()->format('d F Y') }}</strong>
            &nbsp;&bull;&nbsp;
            Classification: <strong>Confidential</strong>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- 1. EXECUTIVE SUMMARY                                                --}}
    {{-- ================================================================== --}}
    <div class="section-title">1. Executive Summary</div>

    <div class="sub-title">1.1 Introduction</div>
    <p>
        This report presents the findings of an ISO 27001:2022 Gap Assessment conducted for
        <strong>{{ $project->name }}</strong>. The assessment was performed to evaluate the
        organisation's current information security posture against the requirements of the
        ISO/IEC 27001:2022 standard and to identify areas requiring remediation prior to
        formal certification.
    </p>

    <div class="sub-title">1.2 Purpose</div>
    <p>
        The purpose of this Gap Assessment is to identify and document the gaps between the
        organisation's existing Information Security Management System (ISMS) controls and
        the requirements stipulated by ISO/IEC 27001:2022. The findings and recommendations
        contained herein are intended to guide the organisation in developing a prioritised
        remediation roadmap.
    </p>

    <div class="sub-title">1.3 Scope</div>
    <p>
        The scope of this assessment encompasses the people, processes, and technology that
        support the organisation's information assets. The assessment covers all Annex A
        controls and clauses 4 through 10 of the ISO/IEC 27001:2022 standard as applicable
        to the defined scope of the ISMS.
    </p>

    <div class="sub-title">1.4 Report Circulation</div>
    <p>
        This report is classified as <strong>Confidential</strong> and is intended solely
        for the use of the organisation's senior management, IT leadership, and designated
        information security personnel. Unauthorised distribution or disclosure of this
        report is strictly prohibited.
    </p>

    <div class="sub-title">1.5 Limitations</div>
    <p>
        This assessment is based on information provided by the organisation's personnel
        through interviews, document reviews, and process walkthroughs conducted during the
        assessment period. The findings reflect the state of the ISMS at the time of
        assessment and may not account for changes implemented after the assessment date.
        This report does not constitute a formal certification audit.
    </p>

    <div class="page-break"></div>

    {{-- ================================================================== --}}
    {{-- 2. SUMMARY OF FINDINGS                                              --}}
    {{-- ================================================================== --}}
    <div class="section-title">2. Summary of Findings</div>
    <p>
        The assessment identified a total of <strong>{{ $stats['total'] }}</strong> findings
        across the evaluated ISO 27001:2022 clauses and Annex A controls. The table below
        provides a breakdown of findings by risk rating.
    </p>

    <table>
        <thead>
            <tr>
                <th style="width:30%">Risk Rating</th>
                <th style="width:35%">Number of Findings</th>
                <th style="width:35%">Percentage of Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="badge badge-high">High</span></td>
                <td>{{ $stats['high_count'] }}</td>
                <td>{{ $stats['high_pct'] }}%</td>
            </tr>
            <tr>
                <td><span class="badge badge-medium">Medium</span></td>
                <td>{{ $stats['medium_count'] }}</td>
                <td>{{ $stats['medium_pct'] }}%</td>
            </tr>
            <tr>
                <td><span class="badge badge-low">Low</span></td>
                <td>{{ $stats['low_count'] }}</td>
                <td>{{ $stats['low_pct'] }}%</td>
            </tr>
            <tr style="font-weight:700; background-color:#f1f5f9;">
                <td>Total</td>
                <td>{{ $stats['total'] }}</td>
                <td>100%</td>
            </tr>
        </tbody>
    </table>

    {{-- ================================================================== --}}
    {{-- 3. KEY CRITICAL FINDINGS (HIGH RISK)                                --}}
    {{-- ================================================================== --}}
    <div class="section-title">3. Key Critical Findings (High Risk)</div>

    @if($highFindings->isEmpty())
        <p>No High risk findings were identified during this assessment.</p>
    @else
        <p>
            The following <strong>{{ $highFindings->count() }}</strong> High risk finding(s)
            require immediate management attention and prioritised remediation:
        </p>
        <table>
            <thead>
                <tr>
                    <th style="width:9%">Serial No.</th>
                    <th style="width:23%">Clause Reference</th>
                    <th style="width:33%">Observation Title</th>
                    <th style="width:35%">Recommendation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($highFindings as $f)
                <tr>
                    <td>{{ $f->serial_no }}</td>
                    <td>{{ $f->clause_reference }}</td>
                    <td>{{ $f->observation_title }}</td>
                    <td>{{ $f->recommendation }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>

    {{-- ================================================================== --}}
    {{-- 4. DETAILED FINDINGS AND RECOMMENDATIONS                            --}}
    {{-- ================================================================== --}}
    <div class="section-title">4. Detailed Findings and Recommendations</div>
    <p>
        The following section provides a comprehensive breakdown of all
        <strong>{{ $stats['total'] }}</strong> findings identified during the assessment,
        including the current state, identified gap, associated risk, and recommended
        remediation actions.
    </p>

    @foreach($findings as $finding)
    <div class="finding-card no-break">

        {{-- Card header --}}
        <div class="finding-card-header">
            <div class="fc-serial">{{ $finding->serial_no }} &mdash; {{ $finding->clause_reference }}</div>
            <div class="fc-title">{{ $finding->observation_title }}</div>
        </div>

        {{-- Card body --}}
        <div class="finding-card-body">
            <table>
                <tr>
                    <td class="label-cell">Risk Rating</td>
                    <td>
                        @if($finding->risk_rating === 'High')
                            <span class="badge badge-high">High</span>
                        @elseif($finding->risk_rating === 'Medium')
                            <span class="badge badge-medium">Medium</span>
                        @else
                            <span class="badge badge-low">Low</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label-cell">Status</td>
                    <td>
                        @if($finding->status === 'Open')
                            <span class="badge badge-open">Open</span>
                        @elseif($finding->status === 'In Progress')
                            <span class="badge badge-progress">In Progress</span>
                        @else
                            <span class="badge badge-closed">Closed</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label-cell">Current State / Observation</td>
                    <td>{{ $finding->current_state }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Gap Description</td>
                    <td>{{ $finding->gap_description }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Impact / Risk</td>
                    <td>{{ $finding->impact_risk }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Recommendation</td>
                    <td>{{ $finding->recommendation }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endforeach

    {{-- ================================================================== --}}
    {{-- FOOTER                                                              --}}
    {{-- ================================================================== --}}
    <div class="report-footer">
        ISO 27001:2022 Gap Assessment Report &mdash; {{ $project->name }}
        &mdash; Generated {{ now()->format('d F Y') }} &mdash; Confidential
    </div>

</div>
</body>
</html>
