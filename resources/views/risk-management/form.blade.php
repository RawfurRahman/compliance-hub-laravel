@extends('layouts.app')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    .font-outfit { font-family: 'Outfit', sans-serif; }
    .glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    }
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .score-badge {
        font-size: 20px;
        font-weight: 900;
        padding: 8px 16px;
        border-radius: 12px;
        text-align: center;
        min-width: 80px;
    }
    .bg-critical { background-color: #fecaca; color: #991b1b; border: 1px solid #fca5a5; }
    .bg-high { background-color: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
    .bg-medium { background-color: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
    .bg-low { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    
    .text-critical { color: #b91c1c; }
    .text-high { color: #ea580c; }
    .text-medium { color: #eab308; }
    .text-low { color: #16a34a; }

    .tab-btn {
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
    }
    .tab-btn-active {
        color: #0f172a;
        border-color: #0f172a;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto font-outfit" x-data="riskForm()">
    
    {{-- Top Navigation & Actions --}}
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('risk-register.index', $project) }}" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition shadow-sm">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                    @if($risk)
                        Edit Risk: <span class="text-sky-600">{{ $risk->risk_id }}</span>
                    @else
                        Create New Risk Entry
                    @endif
                </h1>
                <p class="text-sm text-slate-500 font-medium">
                    {{ $project->name }} &mdash; Risk Management Module
                </p>
            </div>
        </div>
        
        @if($risk)
            <div class="flex gap-2">
                <span class="badge {{ $risk->inherent_risk_level === 'Critical' ? 'bg-critical' : ($risk->inherent_risk_level === 'High' ? 'bg-high' : ($risk->inherent_risk_level === 'Medium' ? 'bg-medium' : 'bg-low')) }}">
                    Inherent: {{ $risk->inherent_risk_level }} ({{ $risk->inherent_score }})
                </span>
                <span class="badge {{ $risk->residual_risk_level === 'Critical' ? 'bg-critical' : ($risk->residual_risk_level === 'High' ? 'bg-high' : ($risk->residual_risk_level === 'Medium' ? 'bg-medium' : 'bg-low')) }}">
                    Residual: {{ $risk->residual_risk_level }} ({{ $risk->residual_score }})
                </span>
            </div>
        @endif
    </div>

    {{-- Main Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        {{-- Navigation Sidebar (Only when editing) --}}
        @if($risk)
            <div class="lg:col-span-1">
                <div class="glass-card rounded-2xl p-4 border border-slate-200 space-y-1">
                    <button @click="activeTab = 'details'" :class="activeTab === 'details' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-semibold transition flex items-center gap-3">
                        <i class="fas fa-edit w-5 text-slate-400"></i> Edit Details
                    </button>
                    <button @click="activeTab = 'controls'" :class="activeTab === 'controls' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-semibold transition flex items-center justify-between">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-shield-halved w-5 text-slate-400"></i> Controls Mapping
                        </span>
                        <span class="bg-slate-200 text-slate-700 text-xs px-2 py-0.5 rounded-full font-bold" x-text="controlCount">0</span>
                    </button>
                    <button @click="activeTab = 'treatments'" :class="activeTab === 'treatments' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-semibold transition flex items-center justify-between">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-screwdriver-wrench w-5 text-slate-400"></i> Treatment Plans
                        </span>
                        <span class="bg-slate-200 text-slate-700 text-xs px-2 py-0.5 rounded-full font-bold" x-text="treatmentCount">0</span>
                    </button>
                    <button @click="activeTab = 'reviews'" :class="activeTab === 'reviews' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-semibold transition flex items-center gap-3">
                        <i class="fas fa-clock-rotate-left w-5 text-slate-400"></i> Workflow & Reviews
                    </button>
                    <button @click="activeTab = 'kris'" :class="activeTab === 'kris' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-semibold transition flex items-center gap-3">
                        <i class="fas fa-chart-line w-5 text-slate-400"></i> KRI Metrics
                    </button>
                    <button @click="activeTab = 'comments'" :class="activeTab === 'comments' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'" class="w-full text-left px-4 py-3 rounded-xl text-sm font-semibold transition flex items-center gap-3">
                        <i class="fas fa-comments w-5 text-slate-400"></i> Comments & Attach.
                    </button>
                </div>
            </div>
        @endif

        {{-- Content Area --}}
        <div class="{{ $risk ? 'lg:col-span-3' : 'lg:col-span-4' }} space-y-6">
            
            {{-- TAB: DETAILS --}}
            <div x-show="activeTab === 'details'" class="glass-card rounded-3xl border border-slate-200 p-8">
                <form action="{{ $risk ? route('risk-register.update', [$project, $risk]) : route('risk-register.store', $project) }}" method="POST" class="space-y-8">
                    @csrf
                    @if($risk)
                        @method('PUT')
                    @endif

                    {{-- Section 1: Identification --}}
                    <div>
                        <h2 class="text-xs font-bold text-sky-600 uppercase tracking-widest border-b-2 border-sky-100 pb-2 mb-6 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> General Identification
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Risk Name *</label>
                                <input type="text" name="risk_name" value="{{ old('risk_name', $risk?->risk_name) }}" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Risk Owner *</label>
                                <input type="text" name="risk_owner" value="{{ old('risk_owner', $risk?->risk_owner) }}" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Department *</label>
                                <select name="department" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                    <option value="">-- Select Department --</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->name }}" {{ old('department', $risk?->department) === $d->name ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Date Identified *</label>
                                <input type="date" name="date_identified" value="{{ old('date_identified', $risk?->date_identified?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Risk Category</label>
                                <select name="risk_category_id" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" {{ old('risk_category_id', $risk?->risk_category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Asset Linkage</label>
                                <select name="risk_asset_id" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                    <option value="">-- Select Asset --</option>
                                    @foreach($assets as $a)
                                        <option value="{{ $a->id }}" {{ old('risk_asset_id', $risk?->risk_asset_id) == $a->id ? 'selected' : '' }}>{{ $a->asset_id }} &mdash; {{ $a->name }} (Value: {{ $a->asset_value }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Risk Description *</label>
                                <textarea name="risk_description" required rows="3" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">{{ old('risk_description', $risk?->risk_description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Threat & Impact Analysis --}}
                    <div>
                        <h2 class="text-xs font-bold text-sky-600 uppercase tracking-widest border-b-2 border-sky-100 pb-2 mb-6 flex items-center gap-2">
                            <i class="fas fa-skull-crossbones"></i> Threat & CIA Assessment
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Threat (1-5)</label>
                                <input type="number" name="threat_score" min="1" max="5" value="{{ old('threat_score', $risk?->threat_score ?? 3) }}" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Confidentiality (1-5)</label>
                                <input type="number" name="confidentiality" min="1" max="5" value="{{ old('confidentiality', $risk?->confidentiality ?? 3) }}" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Integrity (1-5)</label>
                                <input type="number" name="integrity" min="1" max="5" value="{{ old('integrity', $risk?->integrity ?? 3) }}" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Availability (1-5)</label>
                                <input type="number" name="availability" min="1" max="5" value="{{ old('availability', $risk?->availability ?? 3) }}" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Threat Description / Notes</label>
                                <textarea name="threat_description" rows="2" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20" placeholder="Describe the specific threat agents, vulnerability details or CIA impact vector...">{{ old('threat_description', $risk?->threat_description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Scoring Matrix --}}
                    <div>
                        <h2 class="text-xs font-bold text-sky-600 uppercase tracking-widest border-b-2 border-sky-100 pb-2 mb-6 flex items-center gap-2">
                            <i class="fas fa-calculator"></i> 5&times;5 Scoring Assessment (Inherent vs Residual)
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                            {{-- Inherent Risk Score --}}
                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4">
                                <h3 class="text-sm font-bold text-slate-800">Inherent Assessment</h3>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Likelihood (1-5) *</label>
                                    <input type="number" name="likelihood" min="1" max="5" x-model.number="form.likelihood" @input="recalc()" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Impact (1-5) *</label>
                                    <input type="number" name="impact" min="1" max="5" x-model.number="form.impact" @input="recalc()" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                                <div class="pt-2">
                                    <div class="score-badge font-outfit" :class="'bg-' + inherentLevel.toLowerCase()" x-text="inherentScore + ' — ' + inherentLevel"></div>
                                    <span class="block text-[10px] text-slate-400 text-center mt-1">Inherent Score</span>
                                </div>
                            </div>

                            {{-- Residual Risk Score --}}
                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4">
                                <h3 class="text-sm font-bold text-slate-800">Residual Assessment</h3>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Residual Likelihood (1-5) *</label>
                                    <input type="number" name="residual_likelihood" min="1" max="5" x-model.number="form.residual_likelihood" @input="recalc()" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Residual Impact (1-5) *</label>
                                    <input type="number" name="residual_impact" min="1" max="5" x-model.number="form.residual_impact" @input="recalc()" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                                <div class="pt-2">
                                    <div class="score-badge font-outfit" :class="'bg-' + residualLevel.toLowerCase()" x-text="residualScore + ' — ' + residualLevel"></div>
                                    <span class="block text-[10px] text-slate-400 text-center mt-1">Residual Score</span>
                                </div>
                            </div>

                            {{-- Control effectiveness & exposure --}}
                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 flex flex-col justify-between">
                                <h3 class="text-sm font-bold text-slate-800 mb-4">Risk Performance</h3>
                                <div class="space-y-4 text-center my-auto">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Risk Reduction</span>
                                        <span class="text-3xl font-black text-emerald-600" x-text="controlEff + '%'">0%</span>
                                    </div>
                                    <div class="pt-2 border-t border-slate-200">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Financial Exposure ($)</label>
                                        <input type="number" step="0.01" name="financial_exposure" value="{{ old('financial_exposure', $risk?->financial_exposure) }}" class="w-full border-slate-200 rounded-xl px-3 py-2 text-sm text-center focus:border-sky-500 focus:ring-sky-500/20" placeholder="e.g. 50000">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Treatment & Workflows --}}
                    <div>
                        <h2 class="text-xs font-bold text-sky-600 uppercase tracking-widest border-b-2 border-sky-100 pb-2 mb-6 flex items-center gap-2">
                            <i class="fas fa-screwdriver-wrench"></i> Treatment & Review Workflow
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Treatment Decision *</label>
                                <select name="treatment_decision" required class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                    @foreach(\App\Modules\RiskManagement\Models\RiskRegister::TREATMENT_DECISIONS as $td)
                                        <option value="{{ $td }}" {{ old('treatment_decision', $risk?->treatment_decision ?? 'In Review') === $td ? 'selected' : '' }}>{{ $td }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Communication Status</label>
                                <select name="communication_status" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                    <option value="" disabled>-- Select --</option>
                                    <option value="Communicated" {{ old('communication_status', $risk?->communication_status) === 'Communicated' ? 'selected' : '' }}>Communicated</option>
                                    <option value="Pending" {{ old('communication_status', $risk?->communication_status ?? 'Pending') === 'Pending' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Target Completion Date</label>
                                <input type="date" name="target_date" value="{{ old('target_date', $risk?->target_date?->format('Y-m-d')) }}" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Next Review Date</label>
                                <input type="date" name="next_review_date" value="{{ old('next_review_date', $risk?->next_review_date?->format('Y-m-d')) }}" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Implementation Status (Workflow)</label>
                                <select name="status" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                                    @foreach(\App\Modules\RiskManagement\Models\RiskRegister::STATUSES as $st)
                                        <option value="{{ $st }}" {{ old('status', $risk?->status ?? 'Draft') === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Recommended Control Title (General)</label>
                                <input type="text" name="recommended_control" value="{{ old('recommended_control', $risk?->recommended_control) }}" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Follow-up Notes / Activity Updates</label>
                                <textarea name="follow_up_notes" rows="2" class="w-full border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:border-sky-500 focus:ring-sky-500/20" placeholder="Notes on implementation progress or other follow-up tasks...">{{ old('follow_up_notes', $risk?->follow_up_notes) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Form Footer Actions --}}
                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
                        <a href="{{ route('risk-register.index', $project) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition shadow">
                            <i class="fas fa-save mr-1.5"></i> {{ $risk ? 'Update Risk Details' : 'Create Risk Entry' }}
                        </button>
                    </div>
                </form>
            </div>

            @if($risk)
                {{-- TAB: CONTROLS MAPPING --}}
                <div x-show="activeTab === 'controls'" class="glass-card rounded-3xl border border-slate-200 p-8 space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Framework Control Mappings</h2>
                        <p class="text-xs text-slate-400 mt-1">Map specific controls from compliance frameworks to mitigate this risk. This satisfies GRC linkages.</p>
                    </div>

                    {{-- Add Control Mapping Form --}}
                    <form @submit.prevent="mapControl()" class="bg-slate-50 border border-slate-200 p-5 rounded-2xl flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Framework Control</label>
                            <select x-model="mapForm.control_id" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20" required>
                                <option value="">-- Choose Control --</option>
                                @foreach($controls as $fc)
                                    <option value="{{ $fc->id }}">{{ $fc->control_id }} &mdash; {{ Str::limit($fc->requirement_description, 50) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-32">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Effectiveness %</label>
                            <input type="number" min="0" max="100" x-model.number="mapForm.effectiveness" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20" required>
                        </div>
                        <div class="w-40">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Control Type</label>
                            <select x-model="mapForm.type" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                <option value="Preventive">Preventive</option>
                                <option value="Detective">Detective</option>
                                <option value="Corrective">Corrective</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition">
                                Map Control
                            </button>
                        </div>
                    </form>

                    {{-- Mapped Controls List --}}
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-slate-200 tracking-wider">
                                    <th class="px-4 py-3">Control ID</th>
                                    <th class="px-4 py-3">Control Description</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3 text-center">Effectiveness</th>
                                    <th class="px-4 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in mappings" :key="item.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3.5 font-bold text-slate-900 font-mono" x-text="item.control_ref"></td>
                                        <td class="px-4 py-3.5 text-slate-600 font-medium" x-text="item.control_name"></td>
                                        <td class="px-4 py-3.5 text-slate-500" x-text="item.type"></td>
                                        <td class="px-4 py-3.5 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                                    <div class="bg-emerald-500 h-1.5 rounded-full" :style="'width: ' + item.effectiveness + '%'"></div>
                                                </div>
                                                <span class="font-extrabold text-slate-700" x-text="item.effectiveness + '%'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <button @click="unmapControl(item.framework_control_id)" class="text-rose-500 hover:text-rose-700 p-1">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="mappings.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No framework controls mapped to this risk.</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB: TREATMENTS --}}
                <div x-show="activeTab === 'treatments'" class="glass-card rounded-3xl border border-slate-200 p-8 space-y-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Risk Treatments & Mitigations</h2>
                            <p class="text-xs text-slate-400 mt-1">Detailed action items, milestones, budgets, and ownership of risk remediation.</p>
                        </div>
                        <button @click="showAddTreatment = !showAddTreatment" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition">
                            <span x-text="showAddTreatment ? 'Hide Form' : 'Add Treatment'"></span>
                        </button>
                    </div>

                    {{-- Add Treatment Form --}}
                    <div x-show="showAddTreatment" x-cloak class="bg-slate-50 border border-slate-200 p-6 rounded-2xl">
                        <form @submit.prevent="addTreatment()" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Treatment Description *</label>
                                    <textarea x-model="treatForm.description" required rows="2" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Responsible Person *</label>
                                    <input type="text" x-model="treatForm.responsible_person" required class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                                    <select x-model="treatForm.status" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                        <option value="Not Started">Not Started</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Overdue">Overdue</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Estimated Cost ($)</label>
                                    <input type="number" step="0.01" x-model.number="treatForm.estimated_cost" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Target End Date</label>
                                    <input type="date" x-model="treatForm.end_date" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="submit" class="px-5 py-2 bg-[#0a1e42] hover:bg-opacity-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition">
                                    Save Treatment
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Treatments List --}}
                    <div class="grid grid-cols-1 gap-4">
                        <template x-for="item in treatments" :key="item.id">
                            <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-sm transition flex justify-between items-start">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex px-2 py-0.5 text-[9px] font-bold uppercase rounded border"
                                              :class="item.status === 'Completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (item.status === 'In Progress' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-700 border-slate-200')"
                                              x-text="item.status"></span>
                                        <span class="text-xs text-slate-400" x-text="'Resp: ' + item.responsible_person"></span>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700" x-text="item.description"></p>
                                    <div class="flex items-center gap-6 text-xs text-slate-400">
                                        <span x-if="item.end_date"><i class="far fa-calendar mr-1"></i> <span x-text="'Due: ' + item.end_date"></span></span>
                                        <span x-if="item.estimated_cost"><i class="fas fa-dollar-sign mr-1"></i> <span x-text="'Cost: $' + item.estimated_cost"></span></span>
                                    </div>
                                </div>
                                <button @click="deleteTreatment(item.id)" class="text-rose-500 hover:text-rose-700 p-1 self-center">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="treatments.length === 0">
                            <div class="text-center py-10 bg-slate-50 border border-dashed border-slate-350 rounded-2xl text-slate-400 italic text-xs">
                                No specific treatment plan items added yet. Click Add Treatment.
                            </div>
                        </template>
                    </div>
                </div>

                {{-- TAB: REVIEWS & WORKFLOW --}}
                <div x-show="activeTab === 'reviews'" class="glass-card rounded-3xl border border-slate-200 p-8 space-y-8">
                    
                    {{-- Review Outcome Submission --}}
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Risk Review Cycle</h2>
                        <p class="text-xs text-slate-400 mt-1">Submit periodic review outcomes to audit changes in scores or treatment decisions.</p>
                        
                        <form @submit.prevent="submitReview()" class="mt-4 bg-slate-50 border border-slate-200 p-5 rounded-2xl space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Review Outcome *</label>
                                    <select x-model="reviewForm.outcome" required class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                        <option value="No Change">No Change</option>
                                        <option value="Score Updated">Score Updated</option>
                                        <option value="Treatment Changed">Treatment Changed</option>
                                        <option value="Closed">Closed</option>
                                        <option value="Escalated">Escalated</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Next Review Target Date</label>
                                    <input type="date" x-model="reviewForm.next_review_date" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Review Findings / Notes *</label>
                                    <textarea x-model="reviewForm.findings" required rows="2" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20" placeholder="Detail any observations from the audit / review committee..."></textarea>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition">
                                    Submit Review Outcome
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Review cycles & history --}}
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 mb-4">Risk Reviews History</h3>
                        <div class="space-y-4">
                            <template x-for="item in reviews" :key="item.id">
                                <div class="border border-slate-200 rounded-2xl p-4 bg-white hover:shadow-sm transition">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex px-2 py-0.5 text-[9px] font-bold uppercase rounded border bg-slate-100 text-slate-800" x-text="item.outcome"></span>
                                            <span class="text-xs text-slate-400" x-text="'Reviewed by ' + item.reviewer_name"></span>
                                        </div>
                                        <span class="text-xs font-bold text-slate-500" x-text="item.review_date"></span>
                                    </div>
                                    <p class="text-xs text-slate-600 font-medium" x-text="item.findings"></p>
                                </div>
                            </template>
                            <template x-if="reviews.length === 0">
                                <p class="text-center py-6 text-slate-400 italic text-xs bg-slate-50 border border-dashed rounded-xl">No GRC audit review outcomes submitted yet.</p>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- TAB: KRIS --}}
                <div x-show="activeTab === 'kris'" class="glass-card rounded-3xl border border-slate-200 p-8 space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Key Risk Indicators (KRIs)</h2>
                        <p class="text-xs text-slate-400 mt-1">Specify quantitative indicator metrics and trigger threshold bands to monitor risk triggers.</p>
                    </div>

                    <form @submit.prevent="addKri()" class="bg-slate-50 border border-slate-200 p-5 rounded-2xl space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">KRI Indicator Name *</label>
                                <input type="text" x-model="kriForm.kri_name" placeholder="e.g. Failed Login Counts (per Hour)" required class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Unit</label>
                                <input type="text" x-model="kriForm.unit" placeholder="%, count, days" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Current Value</label>
                                <input type="number" step="0.01" x-model.number="kriForm.current_value" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Amber Limit</label>
                                <input type="number" step="0.01" x-model.number="kriForm.threshold_amber" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Red Trigger Limit</label>
                                <input type="number" step="0.01" x-model.number="kriForm.threshold_red" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status Band</label>
                                <select x-model="kriForm.rag_status" class="w-full text-sm border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20">
                                    <option value="Green">Green (Normal)</option>
                                    <option value="Amber">Amber (Warning)</option>
                                    <option value="Red">Red (Triggered)</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition">
                                    Add Indicator
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-slate-200 tracking-wider">
                                    <th class="px-4 py-3">Indicator Name</th>
                                    <th class="px-4 py-3">Amber Trigger</th>
                                    <th class="px-4 py-3">Red Trigger</th>
                                    <th class="px-4 py-3 text-center">Current</th>
                                    <th class="px-4 py-3 text-center">Status Band</th>
                                    <th class="px-4 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in kris" :key="item.id">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3 font-semibold text-slate-800" x-text="item.kri_name"></td>
                                        <td class="px-4 py-3 text-slate-500" x-text="item.threshold_amber + ' ' + (item.unit ?? '')"></td>
                                        <td class="px-4 py-3 text-rose-500 font-semibold" x-text="item.threshold_red + ' ' + (item.unit ?? '')"></td>
                                        <td class="px-4 py-3 text-center font-bold" x-text="(item.current_value ?? '—') + ' ' + (item.unit ?? '')"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase border"
                                                  :class="item.rag_status === 'Red' ? 'bg-rose-50 text-rose-700 border-rose-200' : (item.rag_status === 'Amber' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200')"
                                                  x-text="item.rag_status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button @click="deleteKri(item.id)" class="text-rose-500 hover:text-rose-700 p-1">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="kris.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">No Key Risk Indicators (KRI) mapped.</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB: COMMENTS & ATTACHMENTS --}}
                <div x-show="activeTab === 'comments'" class="glass-card rounded-3xl border border-slate-200 p-8 space-y-8">
                    
                    {{-- Grid for splitting Comments and Evidence --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        {{-- Left Column: GRC Evidence & File Attachments --}}
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Risk Evidence Files</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Attach existing uploaded evidence files from the Evidence Hub or upload a new file.</p>
                            </div>
                            
                            {{-- Attachment Upload / Select Form --}}
                            <form @submit.prevent="attachEvidenceFile()" class="bg-slate-50 border border-slate-200 p-4 rounded-xl space-y-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Select Existing Evidence</label>
                                    <select x-model="attachForm.evidence_id" class="w-full text-xs border-slate-200 rounded-lg p-1.5 focus:border-sky-500 focus:ring-sky-500/20">
                                        <option value="">-- Choose Existing Evidence --</option>
                                        @isset($projectEvidence)
                                            @foreach($projectEvidence as $pe)
                                                <option value="{{ $pe->id }}">{{ $pe->name }} (Uploaded: {{ $pe->created_at->format('Y-m-d') }})</option>
                                            @endforeach
                                        @endisset
                                    </select>
                                </div>
                                <div class="text-center text-slate-400 text-xs font-bold py-1">OR</div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Upload New File</label>
                                    <input type="file" @change="handleFileUpload($event)" class="w-full text-xs border-slate-200 rounded-lg p-1">
                                </div>
                                <div class="flex justify-end pt-2 border-t border-slate-100">
                                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-bold uppercase tracking-wider rounded-lg transition" :disabled="uploading">
                                        <span x-text="uploading ? 'Processing…' : 'Attach File'"></span>
                                    </button>
                                </div>
                            </form>

                            {{-- Attachments List --}}
                            <div class="space-y-2">
                                <template x-for="item in attachments" :key="item.id">
                                    <div class="bg-white border border-slate-200 rounded-xl p-3 hover:shadow-xs transition flex justify-between items-center text-xs">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-file-pdf text-rose-500 text-sm"></i>
                                            <div>
                                                <span class="font-bold text-slate-700 block truncate max-w-[200px]" x-text="item.filename"></span>
                                                <span class="text-[9px] text-slate-400 font-mono" x-text="item.attachment_type"></span>
                                            </div>
                                        </div>
                                        <a :href="'/storage/' + item.path" target="_blank" class="text-sky-600 hover:text-sky-800 font-bold p-1">
                                            <i class="fas fa-arrow-down-long"></i>
                                        </a>
                                    </div>
                                </template>
                                <template x-if="attachments.length === 0">
                                    <p class="text-center py-6 text-slate-400 italic text-[11px] bg-slate-50 rounded-xl border border-dashed">No evidence files linked to this risk entry yet.</p>
                                </template>
                            </div>
                        </div>

                        {{-- Right Column: Comments Box --}}
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Activity Logs & Comments</h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Post updates for the auditors or log internal discussions regarding control remediation.</p>
                            </div>
                            
                            {{-- Add Comment Form --}}
                            <form @submit.prevent="postComment()" class="space-y-2">
                                <textarea x-model="commentForm.body" placeholder="Write comment here..." required rows="2" class="w-full text-xs border-slate-200 rounded-xl py-2 px-3 focus:border-sky-500 focus:ring-sky-500/20"></textarea>
                                <div class="flex items-center justify-between">
                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" x-model="commentForm.is_internal" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500/20">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Internal Only</span>
                                    </label>
                                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-bold uppercase tracking-wider rounded-lg transition">
                                        Post Update
                                    </button>
                                </div>
                            </form>

                            {{-- Comments List --}}
                            <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1">
                                <template x-for="item in comments" :key="item.id">
                                    <div class="border border-slate-200/60 rounded-xl p-3 bg-white space-y-1 hover:shadow-xs transition">
                                        <div class="flex justify-between items-center text-[10px] font-bold">
                                            <div class="flex items-center gap-1.5 text-slate-700">
                                                <span x-text="item.user_name"></span>
                                                <span x-show="item.is_internal" class="bg-amber-100 text-amber-700 px-1 rounded text-[8px] uppercase font-black">Internal</span>
                                            </div>
                                            <span class="text-slate-400" x-text="item.created_at"></span>
                                        </div>
                                        <p class="text-xs text-slate-600 font-medium" x-text="item.body"></p>
                                    </div>
                                </template>
                                <template x-if="comments.length === 0">
                                    <p class="text-center py-6 text-slate-400 italic text-[11px] bg-slate-50 rounded-xl border border-dashed">No activity updates posted yet.</p>
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function riskForm() {
    return {
        activeTab: 'details',
        controlCount: 0,
        treatmentCount: 0,
        showAddTreatment: false,
        uploading: false,
        
        // Scores
        inherentScore: {{ $risk?->inherent_score ?? 9 }},
        residualScore: {{ $risk?->residual_score ?? 4 }},
        inherentLevel: '{{ $risk?->inherent_risk_level ?? "Low" }}',
        residualLevel: '{{ $risk?->residual_risk_level ?? "Low" }}',
        controlEff: {{ $risk ? $risk->risk_reduction_pct : 0.0 }},

        // Live fields bind
        form: {
            likelihood: {{ $risk?->likelihood ?? 3 }},
            impact: {{ $risk?->impact ?? 3 }},
            residual_likelihood: {{ $risk?->residual_likelihood ?? 2 }},
            residual_impact: {{ $risk?->residual_impact ?? 2 }},
        },

        // Mappings
        mappings: {!! json_encode($risk ? $risk->controlMappings->map(fn($cm) => [
            'id' => $cm->id,
            'framework_control_id' => $cm->framework_control_id,
            'control_ref' => $cm->frameworkControl?->control_id ?? 'N/A',
            'control_name' => $cm->frameworkControl?->domain ?? 'N/A',
            'type' => $cm->control_type ?? 'Preventive',
            'effectiveness' => $cm->effectiveness_score ?? 0,
        ]) : []) !!},

        // Treatments
        treatments: {!! json_encode(($risk && method_exists($risk, 'treatments') && $risk->treatments) ? $risk->treatments->map(fn($t) => [
            'id' => $t->id,
            'description' => $t->description,
            'status' => $t->status,
            'estimated_cost' => $t->estimated_cost,
            'end_date' => $t->end_date?->format('Y-m-d'),
            'responsible_person' => $t->responsible_person,
        ]) : []) !!},

        // Reviews
        reviews: {!! json_encode(($risk && method_exists($risk, 'reviews') && $risk->reviews) ? $risk->reviews->map(fn($rv) => [
            'id' => $rv->id,
            'outcome' => $rv->outcome,
            'findings' => $rv->findings,
            'review_date' => $rv->review_date?->format('Y-m-d'),
            'reviewer_name' => $rv->reviewer?->username ?? 'Auditor',
        ]) : []) !!},

        // KRIs
        kris: {!! json_encode(($risk && method_exists($risk, 'kriMetrics') && $risk->kriMetrics) ? $risk->kriMetrics->map(fn($k) => [
            'id' => $k->id,
            'kri_name' => $k->kri_name,
            'unit' => $k->unit,
            'threshold_amber' => $k->threshold_amber,
            'threshold_red' => $k->threshold_red,
            'current_value' => $k->current_value,
            'rag_status' => $k->rag_status,
        ]) : []) !!},

        // Comments & Attachments
        comments: {!! json_encode($risk ? $risk->comments->map(fn($c) => [
            'id' => $c->id,
            'body' => $c->body,
            'is_internal' => (bool)$c->is_internal,
            'user_name' => $c->user?->username ?? 'User',
            'created_at' => $c->created_at?->format('Y-m-d H:i'),
        ]) : []) !!},

        attachments: {!! json_encode(($risk && method_exists($risk, 'attachments') && $risk->attachments) ? $risk->attachments->map(fn($a) => [
            'id' => $a->id,
            'filename' => $a->filename,
            'path' => $a->path,
            'attachment_type' => $a->attachment_type,
        ]) : []) !!},

        // Form templates
        mapForm: { control_id: '', effectiveness: 80, type: 'Preventive' },
        treatForm: { description: '', responsible_person: '', status: 'In Progress', estimated_cost: '', end_date: '' },
        reviewForm: { outcome: 'No Change', findings: '', next_review_date: '' },
        kriForm: { kri_name: '', unit: '', current_value: '', threshold_amber: '', threshold_red: '', rag_status: 'Green' },
        commentForm: { body: '', is_internal: true },
        attachForm: { evidence_id: '', file: null },

        init() {
            this.controlCount = this.mappings.length;
            this.treatmentCount = this.treatments.length;
            this.recalc();
        },

        recalc() {
            const l = parseInt(this.form.likelihood) || 1;
            const i = parseInt(this.form.impact) || 1;
            const rl = parseInt(this.form.residual_likelihood) || 1;
            const ri = parseInt(this.form.residual_impact) || 1;

            this.inherentScore = l * i;
            this.residualScore = rl * ri;
            this.inherentLevel = this.scoreToLevel(this.inherentScore);
            this.residualLevel = this.scoreToLevel(this.residualScore);
            this.controlEff = this.inherentScore > 0
                ? Math.round((1 - this.residualScore / this.inherentScore) * 100 * 10) / 10
                : 0.0;
        },

        scoreToLevel(s) {
            if (s >= 20) return 'Critical';
            if (s >= 12) return 'High';
            if (s >= 6) return 'Medium';
            return 'Low';
        },

        // --- AJAX CRUD Handlers (Phase 7, 9, 10) ---
        
        async mapControl() {
            if (!this.mapForm.control_id) return;
            try {
                // We send it to a general mapping or dedicated pivot route.
                // In Laravel, we can map this on the project controller mapping endpoint.
                const response = await fetch(`/projects/{{ $project->id }}/risk-register/{{ $risk?->id ?? 0 }}/map-control`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.mapForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.mappings = data.mappings;
                    this.controlCount = this.mappings.length;
                    this.mapForm = { control_id: '', effectiveness: 80, type: 'Preventive' };
                }
            } catch (e) {
                alert('Error mapping control.');
            }
        },

        async unmapControl(controlId) {
            try {
                const response = await fetch(`/projects/{{ $project->id }}/risk-register/{{ $risk?->id ?? 0 }}/unmap-control/${controlId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.mappings = data.mappings;
                    this.controlCount = this.mappings.length;
                }
            } catch (e) {
                alert('Error removing control mapping.');
            }
        },

        async addTreatment() {
            try {
                const response = await fetch(`/projects/{{ $project->id }}/risk-register/{{ $risk?->id ?? 0 }}/treatments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.treatForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.treatments = data.treatments;
                    this.treatmentCount = this.treatments.length;
                    this.treatForm = { description: '', responsible_person: '', status: 'In Progress', estimated_cost: '', end_date: '' };
                    this.showAddTreatment = false;
                }
            } catch (e) {
                alert('Error adding treatment plan.');
            }
        },

        async deleteTreatment(id) {
            try {
                const response = await fetch(`/projects/{{ $project->id }}/risk-register/{{ $risk?->id ?? 0 }}/treatments/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.treatments = data.treatments;
                    this.treatmentCount = this.treatments.length;
                }
            } catch (e) {
                alert('Error deleting treatment plan.');
            }
        },

        async submitReview() {
            try {
                const response = await fetch(`/projects/{{ $project->id }}/risk-register/{{ $risk?->id ?? 0 }}/reviews`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.reviewForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.reviews = data.reviews;
                    this.reviewForm = { outcome: 'No Change', findings: '', next_review_date: '' };
                }
            } catch (e) {
                alert('Error saving GRC review outcome.');
            }
        },

        async addKri() {
            try {
                const response = await fetch(`/projects/{{ $project->id }}/risk-register/{{ $risk?->id ?? 0 }}/kris`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.kriForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.kris = data.kris;
                    this.kriForm = { kri_name: '', unit: '', current_value: '', threshold_amber: '', threshold_red: '', rag_status: 'Green' };
                }
            } catch (e) {
                alert('Error adding KRI.');
            }
        },

        async deleteKri(id) {
            try {
                const response = await fetch(`/projects/{{ $project->id }}/risk-register/{{ $risk?->id ?? 0 }}/kris/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.kris = data.kris;
                }
            } catch (e) {
                alert('Error deleting KRI.');
            }
        },

        async postComment() {
            try {
                const response = await fetch(`{{ route('risk-register.comment', [$project, $risk ?? 0]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.commentForm),
                });
                const data = await response.json();
                if (data.success) {
                    const c = data.comment;
                    this.comments.unshift({
                        id: c.id,
                        body: c.body,
                        is_internal: (bool)c.is_internal,
                        user_name: c.user?.username ?? 'User',
                        created_at: new Date().toISOString().slice(0, 16).replace('T', ' '),
                    });
                    this.commentForm = { body: '', is_internal: true };
                }
            } catch (e) {
                alert('Error posting activity update.');
            }
        },

        handleFileUpload(e) {
            this.attachForm.file = e.target.files[0];
        },

        async attachEvidenceFile() {
            if (!this.attachForm.evidence_id && !this.attachForm.file) return;
            this.uploading = true;
            
            const formData = new FormData();
            if (this.attachForm.evidence_id) {
                formData.append('evidence_id', this.attachForm.evidence_id);
            }
            if (this.attachForm.file) {
                formData.append('file', this.attachForm.file);
            }
            formData.append('attachment_type', 'Evidence');

            try {
                const response = await fetch(`{{ route('risk-register.evidence', [$project, $risk ?? 0]) }}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });
                const data = await response.json();
                if (data.success) {
                    // Quick refresh of attachments list (or full page reload or mock reload)
                    window.location.reload();
                }
            } catch(e) {
                alert('Error uploading/attaching file.');
            } finally {
                this.uploading = false;
            }
        }
    };
}
</script>
@endpush
