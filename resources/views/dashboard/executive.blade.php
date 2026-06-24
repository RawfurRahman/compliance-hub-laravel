@extends('layouts.app')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    .font-outfit { font-family: 'Outfit', sans-serif; }
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
    }
    .gradient-text {
        background: linear-gradient(135deg, #0a1e42 0%, #1e40af 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
</style>
@endpush

@section('content')
<div class="space-y-6 font-outfit max-w-full" x-data="executiveDashboard()">

    {{-- Top Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                Executive <span class="gradient-text">GRC Dashboard</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">
                Enterprise compliance posture, risk heatmap, controls effectiveness, and GRC maturity metrics.
            </p>
        </div>
        <button type="button" @click="loadData()" :disabled="loading"
                class="self-start sm:self-center inline-flex items-center gap-2 px-4 py-2 bg-[#0a1e42] hover:bg-opacity-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-sm disabled:opacity-50">
            <i class="fas fa-arrows-rotate" :class="loading ? 'fa-spin' : ''"></i>
            Refresh Data
        </button>
    </div>

    {{-- Filters Section --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap gap-4 items-end mt-6">
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Business Unit</label>
            <select x-model="filters.department" @change="loadData()" class="w-full text-sm border-slate-300 rounded-xl shadow-sm focus:border-[#0a1e42] focus:ring focus:ring-[#0a1e42] focus:ring-opacity-20 py-2 px-3">
                <option value="">All Departments</option>
                <option value="IT Department">IT Department</option>
                <option value="Finance">Finance</option>
                <option value="HR">HR</option>
                <option value="Operations">Operations</option>
                <option value="Compliance">Compliance</option>
                <option value="IT">IT</option>
            </select>
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Framework</label>
            <select x-model="filters.framework" @change="loadData()" class="w-full text-sm border-slate-300 rounded-xl shadow-sm focus:border-[#0a1e42] focus:ring focus:ring-[#0a1e42] focus:ring-opacity-20 py-2 px-3">
                <option value="">All Frameworks</option>
                <option value="SOC 2">SOC 2</option>
                <option value="ISO 27001">ISO 27001</option>
                <option value="HIPAA">HIPAA</option>
                <option value="PCI DSS">PCI DSS</option>
                <option value="Risk Register">Risk Register</option>
            </select>
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Risk Type</label>
            <select x-model="filters.risk_type" @change="loadData()" class="w-full text-sm border-slate-300 rounded-xl shadow-sm focus:border-[#0a1e42] focus:ring focus:ring-[#0a1e42] focus:ring-opacity-20 py-2 px-3">
                <option value="">All Risk Types</option>
                <option value="High">High Risk</option>
                <option value="Medium">Medium Risk</option>
                <option value="Low">Low Risk</option>
                <option value="None">No Risk</option>
            </select>
        </div>
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Owner</label>
            <select x-model="filters.owner" @change="loadData()" class="w-full text-sm border-slate-300 rounded-xl shadow-sm focus:border-[#0a1e42] focus:ring focus:ring-[#0a1e42] focus:ring-opacity-20 py-2 px-3">
                <option value="">All Owners</option>
                <option value="IT">IT</option>
                <option value="HR">HR</option>
                <option value="Compliance">Compliance</option>
                <option value="Procurement">Procurement</option>
                <option value="CISO">CISO</option>
            </select>
        </div>
        <div>
            <button type="button" @click="clearFilters()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-sm border border-slate-200">
                Clear
            </button>
        </div>
    </div>

    {{-- Global Loading State --}}
    <div x-show="loading" class="flex flex-col items-center justify-center py-20 bg-white border border-slate-200 rounded-2xl shadow-sm space-y-4 mt-6">
        <div class="w-12 h-12 border-4 border-[#0a1e42] border-t-transparent rounded-full animate-spin"></div>
        <p class="text-sm font-semibold text-slate-500">Aggregating compliance metrics & calculating maturity scores...</p>
    </div>

    {{-- Global Error State --}}
    <div x-show="!loading && error" x-cloak class="p-8 bg-rose-50 border border-rose-200 rounded-2xl text-center space-y-4 max-w-md mx-auto my-10">
        <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto text-xl">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 class="text-lg font-bold text-rose-800">Failed to Load Dashboard Data</h3>
        <p class="text-xs text-rose-600">There was an error communicating with the GRC aggregation endpoints. Please check your database connection.</p>
        <button type="button" @click="loadData()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow">
            Retry Connection
        </button>
    </div>

    {{-- Main Dashboard Layout --}}
    <div x-show="!loading && !error" x-cloak class="space-y-6">

        {{-- 1. Headline KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Projects</p>
                <p class="mt-3 text-4xl font-extrabold text-slate-800" x-text="kpis.projects"></p>
                <p class="mt-1 text-xs text-slate-400">in scope</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Frameworks</p>
                <p class="mt-3 text-4xl font-extrabold text-slate-800" x-text="kpis.frameworks"></p>
                <p class="mt-1 text-xs text-slate-400">governing compliance</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Controls</p>
                <p class="mt-3 text-4xl font-extrabold text-slate-800" x-text="kpis.total_controls"></p>
                <p class="mt-1 text-xs text-emerald-600 font-bold" x-text="kpis.compliant + ' (' + kpis.compliance_pct + '%) compliant'"></p>
            </div>

            <div class="bg-rose-50 rounded-2xl border border-rose-200/60 shadow-sm p-5 hover:shadow-md transition">
                <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Open / Overdue Gaps</p>
                <div class="flex items-baseline gap-2 mt-3">
                    <span class="text-4xl font-extrabold text-rose-700" x-text="kpis.open_findings"></span>
                    <span class="text-sm font-bold text-rose-500" x-text="'/ ' + kpis.overdue_findings + ' overdue'"></span>
                </div>
                <p class="mt-1 text-xs text-rose-600">require immediate action</p>
            </div>
        </div>

        {{-- 2. GRC Maturity Score Standout Panel --}}
        <div class="bg-gradient-to-br from-[#0c1a30] via-[#051021] to-[#01060d] border border-slate-800 rounded-3xl p-6 lg:p-8 text-white shadow-xl flex flex-col lg:flex-row gap-8 items-stretch relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Composite Score Large Badge --}}
            <div class="flex flex-col justify-between w-full lg:w-1/3 bg-white/5 border border-white/10 rounded-2xl p-6 text-center space-y-4">
                <div>
                    <span class="text-[10px] font-black text-sky-400 uppercase tracking-widest">GRC Maturity Index</span>
                    <div class="text-6xl font-black mt-2 tracking-tight" x-text="maturity.composite"></div>
                    <p class="text-xs font-bold text-indigo-200 mt-2" x-text="getMaturityLevelName(maturity.composite)"></p>
                </div>

                {{-- Horizontal GRC Levels strip --}}
                <div>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-2">GRC Maturity Scale</span>
                    <div class="flex gap-1">
                        <template x-for="level in [1, 2, 3, 4, 5]" :key="level">
                            <div class="flex-1 py-1 px-0.5 rounded text-[9px] font-black transition border"
                                 :class="Math.round(maturity.composite) === level 
                                    ? 'bg-gradient-to-r from-sky-400 to-indigo-500 border-transparent text-white shadow-md' 
                                    : 'bg-white/5 border-white/5 text-slate-500'">
                                <span x-text="'L' + level"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 4 Dimension score bar charts --}}
            <div class="flex-1 flex flex-col justify-between space-y-4">
                <div>
                    <h3 class="text-lg font-bold">Maturity Dimensions Breakdown</h3>
                    <p class="text-xs text-slate-400 mt-1">Calculated across risk register parameters, control compliance, evidence files scan validation, and remediation speed.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="dim in [
                        { label: 'Risk Management', key: 'risk_management', desc: 'Weighted Risk Register and acceptance split' },
                        { label: 'Control Design Effectiveness', key: 'control_design', desc: 'Direct mapping of compliance scores' },
                        { label: 'Remediation Velocity', key: 'remediation_velocity', desc: 'Time taken to resolve findings' },
                        { label: 'Evidence Audit Auditability', key: 'evidence_audit', desc: 'Validated evidence uploads' }
                    ]">
                        <div class="bg-white/5 border border-white/5 rounded-xl p-4">
                            <div class="flex items-center justify-between font-bold mb-1.5">
                                <span class="text-xs text-slate-200" x-text="dim.label"></span>
                                <span class="text-sky-400 text-xs" x-text="'Level ' + maturity[dim.key]"></span>
                            </div>
                            <div class="h-2 w-full bg-white/10 rounded-full overflow-hidden flex mb-2">
                                <template x-for="step in [1, 2, 3, 4, 5]">
                                    <div class="flex-1 h-full border-r border-[#051021] last:border-0"
                                         :class="maturity[dim.key] >= step ? 'bg-sky-400' : 'bg-white/10'"></div>
                                </template>
                            </div>
                            <p class="text-[10px] text-slate-400" x-text="dim.desc"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- 3. Risk Heatmap & Control Effectiveness --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Risk Heatmap Grid --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-1">Risk Heatmap Matrix</h3>
                    <p class="text-xs text-slate-500 mb-6">Critical mapping of likelihood (Remediation Status) vs impact (Risk Rating).</p>
                </div>

                <div class="grid grid-cols-4 gap-2 text-center text-xs font-bold my-auto">
                    <!-- Blank Corner -->
                    <div></div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider py-1">Open</div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider py-1">In Progress</div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider py-1">Closed</div>

                    <!-- Row High -->
                    <div class="flex items-center justify-end pr-2 text-[10px] font-black text-slate-500 uppercase tracking-wider">High</div>
                    <div class="p-4 rounded-xl border bg-rose-50 border-rose-200 text-rose-800 flex items-center justify-center" x-text="getHeatmapCount('Open', 'High')"></div>
                    <div class="p-4 rounded-xl border bg-amber-50 border-amber-200 text-amber-800 flex items-center justify-center" x-text="getHeatmapCount('In Progress', 'High')"></div>
                    <div class="p-4 rounded-xl border bg-yellow-50 border-yellow-200 text-yellow-800 flex items-center justify-center" x-text="getHeatmapCount('Closed', 'High')"></div>

                    <!-- Row Medium -->
                    <div class="flex items-center justify-end pr-2 text-[10px] font-black text-slate-500 uppercase tracking-wider">Medium</div>
                    <div class="p-4 rounded-xl border bg-amber-50 border-amber-200 text-amber-800 flex items-center justify-center" x-text="getHeatmapCount('Open', 'Medium')"></div>
                    <div class="p-4 rounded-xl border bg-yellow-50 border-yellow-200 text-yellow-800 flex items-center justify-center" x-text="getHeatmapCount('In Progress', 'Medium')"></div>
                    <div class="p-4 rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-800 flex items-center justify-center" x-text="getHeatmapCount('Closed', 'Medium')"></div>

                    <!-- Row Low -->
                    <div class="flex items-center justify-end pr-2 text-[10px] font-black text-slate-500 uppercase tracking-wider">Low</div>
                    <div class="p-4 rounded-xl border bg-yellow-50 border-yellow-200 text-yellow-800 flex items-center justify-center" x-text="getHeatmapCount('Open', 'Low')"></div>
                    <div class="p-4 rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-800 flex items-center justify-center" x-text="getHeatmapCount('In Progress', 'Low')"></div>
                    <div class="p-4 rounded-xl border bg-emerald-100 border-emerald-300 text-emerald-950 flex items-center justify-center" x-text="getHeatmapCount('Closed', 'Low')"></div>
                </div>

                <div class="mt-4 flex justify-between text-[10px] text-slate-400 uppercase tracking-wider px-2">
                    <span>Low Likelihood &rarr; High</span>
                    <span>Low Impact &rarr; High</span>
                </div>
            </div>

            {{-- Control Effectiveness Donut --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-1">Control Effectiveness</h3>
                    <p class="text-xs text-slate-500 mb-6">Compliant (effective), In Progress (partial), and Non-Compliant (ineffective) breakdown.</p>
                </div>
                <div class="h-60 relative flex items-center justify-center">
                    <canvas id="effectiveness-chart"></canvas>
                </div>
            </div>

        </div>

        {{-- 4. Inherent vs Residual risk bar chart --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-1">Inherent vs Residual Risk by Department</h3>
                <p class="text-xs text-slate-500 mb-6">Weighted risk values aggregated across control domains.</p>
            </div>
            <div class="h-80 relative">
                <canvas id="inherent-residual-chart"></canvas>
            </div>
        </div>

        {{-- 5. Data Tables (Top Risks & Scorecard) --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            {{-- Top GRC Risks Leaderboard --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Top 10 Ranked GRC Gaps</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="px-4 py-2.5">Control</th>
                                    <th class="px-4 py-2.5">Title / Observation</th>
                                    <th class="px-4 py-2.5">Framework</th>
                                    <th class="px-4 py-2.5 text-center">Score</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="risk in topRisks" :key="risk.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3 font-mono font-bold text-slate-800" x-text="risk.control"></td>
                                        <td class="px-4 py-3 text-slate-700 max-w-xs truncate font-medium" x-text="risk.title"></td>
                                        <td class="px-4 py-3 text-slate-500" x-text="risk.framework"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex px-2 py-0.5 rounded-full font-extrabold text-[10px] uppercase border"
                                                  :class="risk.risk === 'High' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                                  x-text="risk.risk + ' (' + risk.risk_score + ')'"></span>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="topRisks.length === 0">
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 italic">No open risk gaps found. All systems compliant!</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Compliance Scorecard Table --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Framework Compliance Scorecard</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="px-4 py-2.5">Framework Name</th>
                                    <th class="px-4 py-2.5 text-center">Status / Phase</th>
                                    <th class="px-4 py-2.5 text-center">Compliance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="row in scorecard" :key="row.framework">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-4 font-bold text-slate-800">
                                            <a :href="row.slug ? '/projects/1/assessments/' + row.slug + '/gap' : '#'" class="hover:underline text-[#0a1e42]" x-text="row.framework"></a>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex px-3 py-1 rounded-full font-bold text-[10px] border"
                                                  :class="getPhaseBadgeClass(row.phase)"
                                                  x-text="getPhaseName(row.phase)"></span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="w-20 bg-slate-100 rounded-full h-1.5">
                                                    <div class="bg-[#0a1e42] h-1.5 rounded-full" :style="'width: ' + row.percentage + '%'"></div>
                                                </div>
                                                <span class="font-extrabold text-slate-800" x-text="row.percentage + '%'"></span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function executiveDashboard() {
    return {
        loading: true,
        error: false,
        kpis: {},
        heatmapCells: [],
        topRisks: [],
        scorecard: [],
        maturity: {},
        inherentResidual: [],
        controlEffectiveness: {},
        charts: {},
        filters: { department: '', framework: '', risk_type: '', owner: '' },

        async init() {
            await this.loadData();
        },

        clearFilters() {
            this.filters = { department: '', framework: '', risk_type: '', owner: '' };
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            this.error = false;
            
            const params = new URLSearchParams();
            if (this.filters.department) params.append('department', this.filters.department);
            if (this.filters.framework) params.append('framework', this.filters.framework);
            if (this.filters.risk_type) params.append('risk_type', this.filters.risk_type);
            if (this.filters.owner) params.append('owner', this.filters.owner);
            const qs = params.toString() ? '?' + params.toString() : '';

            try {
                const [kpisRes, heatmapRes, topRisksRes, maturityRes, inherentRes, controlRes, scorecardRes] = await Promise.all([
                    fetch('{{ route("dashboard.kpis") }}' + qs).then(r => r.json()),
                    fetch('{{ route("dashboard.heatmap") }}' + qs).then(r => r.json()),
                    fetch('{{ route("dashboard.top-risks") }}' + qs).then(r => r.json()),
                    fetch('{{ route("dashboard.maturity-score") }}' + qs).then(r => r.json()),
                    fetch('{{ route("dashboard.inherent-vs-residual") }}' + qs).then(r => r.json()),
                    fetch('{{ route("dashboard.control-effectiveness") }}' + qs).then(r => r.json()),
                    fetch('{{ route("dashboard.compliance-scorecard") }}' + qs).then(r => r.json())
                ]);

                this.kpis = kpisRes.data;
                this.heatmapCells = heatmapRes;
                this.topRisks = topRisksRes;
                this.maturity = maturityRes.data;
                this.inherentResidual = inherentRes;
                this.controlEffectiveness = controlRes.data;
                this.scorecard = scorecardRes;

                // Render/Re-render charts
                this.$nextTick(() => {
                    this.initCharts();
                });
            } catch (err) {
                console.error("Dashboard metrics load error:", err);
                this.error = true;
            } finally {
                this.loading = false;
            }
        },

        initCharts() {
            // Destroy existing charts to prevent memory leaks or visual glitches on refresh
            if (this.charts.effectiveness) this.charts.effectiveness.destroy();
            if (this.charts.inherentResidual) this.charts.inherentResidual.destroy();

            // Control Effectiveness Donut
            const ctxDonut = document.getElementById('effectiveness-chart')?.getContext('2d');
            if (ctxDonut) {
                this.charts.effectiveness = new Chart(ctxDonut, {
                    type: 'doughnut',
                    data: {
                        labels: ['Effective (Compliant)', 'Partial (In Progress)', 'Ineffective (Non-Compliant)'],
                        datasets: [{
                            data: [
                                this.controlEffectiveness.effective || 0,
                                this.controlEffectiveness.partial || 0,
                                this.controlEffectiveness.ineffective || 0
                            ],
                            backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10, family: 'Outfit' }
                                }
                            }
                        }
                    }
                });
            }

            // Inherent vs Residual Bar
            const ctxBar = document.getElementById('inherent-residual-chart')?.getContext('2d');
            if (ctxBar) {
                const labels = this.inherentResidual.map(d => d.department);
                const inherentData = this.inherentResidual.map(d => d.inherent);
                const residualData = this.inherentResidual.map(d => d.residual);

                this.charts.inherentResidual = new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Inherent Risk (Weighted)',
                                data: inherentData,
                                backgroundColor: 'rgba(239, 68, 68, 0.75)',
                                borderColor: '#ef4444',
                                borderWidth: 1
                            },
                            {
                                label: 'Residual Risk (Weighted)',
                                data: residualData,
                                backgroundColor: 'rgba(245, 158, 11, 0.75)',
                                borderColor: '#f59e0b',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10, family: 'Outfit' }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Weighted Score',
                                    font: { family: 'Outfit', size: 10 }
                                }
                            },
                            x: {
                                ticks: {
                                    font: { family: 'Outfit', size: 10 }
                                }
                            }
                        }
                    }
                });
            }
        },

        getMaturityLevelName(score) {
            const val = Math.round(score);
            return {
                1: 'Level 1: Initial (Ad-hoc)',
                2: 'Level 2: Managed',
                3: 'Level 3: Defined',
                4: 'Level 4: Quantitatively Managed',
                5: 'Level 5: Optimizing'
            }[val] || 'Level 1: Initial';
        },

        getHeatmapCount(likelihood, impact) {
            const cell = this.heatmapCells.find(c => c.likelihood === likelihood && c.impact === impact);
            return cell ? cell.count : 0;
        },

        getPhaseBadgeClass(phase) {
            return {
                'gap_in_progress': 'bg-amber-50 text-amber-700 border-amber-200',
                'gap_done': 'bg-sky-50 text-sky-750 border-sky-250',
                'final_pending': 'bg-blue-50 text-blue-700 border-blue-200',
                'final_in_progress': 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'final_done': 'bg-emerald-50 text-emerald-700 border-emerald-200'
            }[phase] || 'bg-slate-50 text-slate-700 border-slate-200';
        },

        getPhaseName(phase) {
            return {
                'gap_in_progress': 'Gap Assessment In Progress',
                'gap_done': 'Gap Assessment Done',
                'final_pending': 'Final Assessment Pending',
                'final_in_progress': 'Final Assessment In Progress',
                'final_done': 'Final Assessment Completed (100% Compliant)'
            }[phase] || phase;
        }
    };
}
</script>
@endpush
