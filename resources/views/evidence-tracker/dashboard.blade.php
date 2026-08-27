@extends('layouts.app')

@push('styles')
    <link href="{{ asset('fonts/outfit.css') }}" rel="stylesheet">
    <style nonce="{{ $cspNonce }}">
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        [x-cloak] { display: none !important; }
        .tracker-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-pending { background: #f1f5f9; color: #64748b; }
        .status-awaiting_review { background: #fef3c7; color: #92400e; }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-gap_assessment_sent { background: #ede9fe; color: #5b21b6; }
        .status-final_report_ready { background: #ccfbf1; color: #134e4a; }
        .status-risk_created { background: #fee2e2; color: #991b1b; }
        .status-rejected { background: #fce7f3; color: #9d174d; }
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .kpi-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .kpi-value { font-size: 28px; font-weight: 800; line-height: 1; }
        .kpi-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; margin-top: 4px; }
        .action-btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.15s;
            cursor: pointer;
            border: none;
        }
        .action-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover:not(:disabled) { background: #4f46e5; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover:not(:disabled) { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover:not(:disabled) { background: #dc2626; }
        .btn-amber { background: #f59e0b; color: white; }
        .btn-amber:hover:not(:disabled) { background: #d97706; }
        .filter-pill {
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid #e2e8f0;
            background: white;
        }
        .filter-pill.active { background: #6366f1; color: white; border-color: #6366f1; }
        .filter-pill:hover:not(.active) { background: #f1f5f9; }
        .workflow-timeline { position: relative; padding-left: 20px; }
        .workflow-timeline::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-dot {
            position: absolute;
            left: 0;
            top: 4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid white;
            background: #6366f1;
        }
    </style>
@endpush

@section('content')
<div class="p-8 min-h-screen" x-data="evidenceTracker()" x-init="init()">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Evidence Tracker</h1>
            <p class="text-gray-500 mt-1 text-sm">{{ $project->name }} — End-to-end evidence workflow orchestration</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500 font-medium">{{ $total }} total evidence files</span>
            <a href="{{ route('evidence.hub', $project) }}" class="action-btn btn-primary">
                <i class="fas fa-arrow-left mr-1"></i> Evidence Hub
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-8 gap-4 mb-8">
        <div class="kpi-card cursor-pointer" @click="filter = ''" :class="filter === '' ? 'ring-2 ring-indigo-500' : ''">
            <div class="kpi-value text-gray-900">{{ $total }}</div>
            <div class="kpi-label">All Files</div>
        </div>
        <div class="kpi-card cursor-pointer" @click="filter = 'submitted'" :class="filter === 'submitted' ? 'ring-2 ring-blue-500' : ''">
            <div class="kpi-value text-blue-600">{{ $status_counts['submitted'] ?? 0 }}</div>
            <div class="kpi-label">Awaiting Approval</div>
        </div>
        <div class="kpi-card cursor-pointer" @click="filter = 'approved'" :class="filter === 'approved' ? 'ring-2 ring-emerald-500' : ''">
            <div class="kpi-value text-emerald-600">{{ $status_counts['approved'] ?? 0 }}</div>
            <div class="kpi-label">Approved</div>
        </div>
        <div class="kpi-card cursor-pointer" @click="filter = 'gap_assessment_sent'" :class="filter === 'gap_assessment_sent' ? 'ring-2 ring-violet-500' : ''">
            <div class="kpi-value text-violet-600">{{ $status_counts['gap_assessment_sent'] ?? 0 }}</div>
            <div class="kpi-label">Gap Assessment</div>
        </div>
        <div class="kpi-card cursor-pointer" @click="filter = 'final_report_ready'" :class="filter === 'final_report_ready' ? 'ring-2 ring-teal-500' : ''">
            <div class="kpi-value text-teal-600">{{ $status_counts['final_report_ready'] ?? 0 }}</div>
            <div class="kpi-label">Final Report</div>
        </div>
        <div class="kpi-card cursor-pointer" @click="filter = 'risk_created'" :class="filter === 'risk_created' ? 'ring-2 ring-red-500' : ''">
            <div class="kpi-value text-red-600">{{ $status_counts['risk_created'] ?? 0 }}</div>
            <div class="kpi-label">Risks Created</div>
        </div>
        <div class="kpi-card cursor-pointer" @click="filter = 'rejected'" :class="filter === 'rejected' ? 'ring-2 ring-pink-500' : ''">
            <div class="kpi-value text-pink-600">{{ $status_counts['rejected'] ?? 0 }}</div>
            <div class="kpi-label">Rejected</div>
        </div>
        <div class="kpi-card cursor-pointer" @click="filter = 'pending'" :class="filter === 'pending' ? 'ring-2 ring-gray-500' : ''">
            <div class="kpi-value text-gray-500">{{ $status_counts['pending'] ?? 0 }}</div>
            <div class="kpi-label">Pending</div>
        </div>
    </div>

    {{-- Actions Bar --}}
    <div class="tracker-card p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <input type="checkbox" @change="toggleSelectAll($event.target.checked)" class="rounded border-gray-300">
            <span class="text-sm text-gray-600 font-medium" x-text="selectedIds.length ? selectedIds.length + ' selected' : 'Select all'"></span>
        </div>
        <div class="flex items-center gap-2">
            <button class="action-btn btn-primary" @click="bulkSendToGap()" :disabled="!selectedIds.length">
                <i class="fas fa-paper-plane mr-1"></i> Send to Gap Assessment
            </button>
            <button class="action-btn btn-success" @click="bulkPassToFinal()" :disabled="!selectedIds.length">
                <i class="fas fa-flag-checkered mr-1"></i> Pass to Final Report
            </button>
            <button class="action-btn btn-amber" @click="bulkCreateRisks()" :disabled="!selectedIds.length">
                <i class="fas fa-shield-alt mr-1"></i> Auto-Create Risks
            </button>
        </div>
    </div>

    {{-- Evidence Table --}}
    <div class="tracker-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="py-4 px-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">
                            <input type="checkbox" @change="toggleSelectAll($event.target.checked)" class="rounded border-gray-300">
                        </th>
                        <th class="py-4 px-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">File / Control</th>
                        <th class="py-4 px-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Requirement</th>
                        <th class="py-4 px-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tracker Status</th>
                        <th class="py-4 px-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Risk Rating</th>
                        <th class="py-4 px-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Analysis Data</th>
                        <th class="py-4 px-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="file in filteredFiles" :key="file.id">
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-4">
                                <input type="checkbox" :value="file.id" x-model="selectedIds" class="rounded border-gray-300">
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-medium text-gray-900" x-text="file.original_filename"></div>
                                <div class="text-xs text-gray-500" x-text="'Control: ' + file.control_ref"></div>
                                <div class="text-xs text-gray-400" x-text="'Uploaded: ' + file.uploaded_by"></div>
                            </td>
                            <td class="py-4 px-4 max-w-xs">
                                <div class="text-gray-700 text-xs leading-relaxed truncate" x-text="file.requirement_description"></div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="status-badge" :class="'status-' + file.tracker_status" x-text="formatStatus(file.tracker_status)"></span>
                                <template x-if="file.approved_by">
                                    <div class="text-xs text-gray-400 mt-1" x-text="'by ' + file.approved_by"></div>
                                </template>
                            </td>
                            <td class="py-4 px-4">
                                <template x-if="file.risk_rating">
                                    <span class="font-semibold text-sm" :class="{
                                        'text-red-600': file.risk_rating === 'High' || file.risk_rating === 'Critical',
                                        'text-amber-600': file.risk_rating === 'Medium',
                                        'text-emerald-600': file.risk_rating === 'Low',
                                        'text-gray-400': !file.risk_rating || file.risk_rating === 'None'
                                    }" x-text="file.risk_rating || 'N/A'"></span>
                                </template>
                            </td>
                            <td class="py-4 px-4">
                                <button @click="toggleReportData(file.id)" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                    <i class="fas" :class="expandedId === file.id ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    <span x-text="expandedId === file.id ? 'Hide' : 'View'"></span>
                                </button>
                                <template x-if="expandedId === file.id">
                                    <div class="mt-2 p-3 bg-gray-50 rounded-lg text-xs space-y-1">
                                        <template x-if="file.gap_description">
                                            <div><span class="font-medium text-gray-600">Gap:</span> <span class="text-gray-700" x-text="file.gap_description"></span></div>
                                        </template>
                                        <template x-if="file.is_compliant !== null">
                                            <div>
                                                <span class="font-medium text-gray-600">Compliant:</span>
                                                <span :class="file.is_compliant ? 'text-emerald-600' : 'text-red-600'" x-text="file.is_compliant ? 'Yes' : 'No'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col gap-1.5">
                                    <button class="action-btn btn-primary text-xs"
                                        @click="sendToGap(file.id)"
                                        :disabled="!file.can_send_to_gap"
                                        x-show="file.can_send_to_gap">
                                        <i class="fas fa-paper-plane mr-1"></i> Send to Gap
                                    </button>
                                    <button class="action-btn btn-success text-xs"
                                        @click="passToFinal(file.id)"
                                        :disabled="!file.can_pass_to_final"
                                        x-show="file.can_pass_to_final">
                                        <i class="fas fa-flag-checkered mr-1"></i> Final Report
                                    </button>
                                    <button class="action-btn btn-amber text-xs"
                                        @click="createRisk(file.id)"
                                        :disabled="!file.can_create_risk"
                                        x-show="file.can_create_risk">
                                        <i class="fas fa-shield-alt mr-1"></i> Create Risk
                                    </button>
                                    <button class="action-btn text-xs border border-gray-300 text-gray-600 hover:bg-gray-100"
                                        @click="showHistory(file.id)">
                                        <i class="fas fa-history mr-1"></i> History
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <template x-if="historyForId === file.id && file.workflow_logs && file.workflow_logs.length">
                            <tr class="bg-gray-50">
                                <td colspan="7" class="py-4 px-8">
                                    <div class="text-sm font-medium text-gray-700 mb-3">Workflow History</div>
                                    <div class="workflow-timeline space-y-3">
                                        <template x-for="log in file.workflow_logs" :key="log.id">
                                            <div class="relative pl-6 pb-2">
                                                <div class="timeline-dot"></div>
                                                <div class="text-xs">
                                                    <span class="font-medium text-gray-800" x-text="log.from_status ? formatStatus(log.from_status) + ' → ' : ''"></span>
                                                    <span class="font-semibold text-indigo-600" x-text="formatStatus(log.to_status)"></span>
                                                </div>
                                                <div class="text-xs text-gray-500" x-text="log.notes || ''"></div>
                                                <div class="text-xs text-gray-400" x-text="log.user ? 'by ' + log.user.username : ''"></div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <template x-if="!filteredFiles.length">
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                    <p class="text-lg font-medium">No evidence files found</p>
                                    <p class="text-sm mt-1">Upload evidence files and complete AI analysis to get started.</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
function evidenceTracker() {
    return {
        filter: '',
        selectedIds: [],
        expandedId: null,
        historyForId: null,
        files: @json($evidence_files),

        get filteredFiles() {
            if (!this.filter) return this.files;
            return this.files.filter(f => f.tracker_status === this.filter);
        },

        init() {},

        formatStatus(status) {
            const labels = {
                'pending': 'Pending',
                'awaiting_review': 'Awaiting Review',
                'submitted': 'Submitted',
                'approved': 'Approved',
                'gap_assessment_sent': 'Gap Assessment Sent',
                'final_report_ready': 'Final Report Ready',
                'risk_created': 'Risk Created',
                'rejected': 'Rejected',
            };
            return labels[status] || status;
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selectedIds = this.filteredFiles.map(f => f.id);
            } else {
                this.selectedIds = [];
            }
        },

        toggleReportData(id) {
            this.expandedId = this.expandedId === id ? null : id;
            this.historyForId = null;
        },

        showHistory(id) {
            this.historyForId = this.historyForId === id ? null : id;
            this.expandedId = null;
        },

        async sendToGap(id) {
            if (!confirm('Send this evidence to the gap assessment?')) return;
            const res = await fetch('/evidence/' + id + '/send-to-gap-assessment', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error sending to gap assessment');
            }
        },

        async passToFinal(id) {
            if (!confirm('Flag this evidence for final report?')) return;
            const res = await fetch('/evidence/' + id + '/pass-to-final-report', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error passing to final report');
            }
        },

        async createRisk(id) {
            if (!confirm('Auto-create a risk register entry from this gap?')) return;
            const res = await fetch('/evidence/' + id + '/auto-create-risk', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error creating risk');
            }
        },

        async bulkSendToGap() {
            if (!confirm('Send ' + this.selectedIds.length + ' evidence files to gap assessment?')) return;
            const res = await fetch('{{ route("evidence.bulk-send-to-gap", $project) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ evidence_ids: this.selectedIds })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Bulk operation failed');
            }
        },

        async bulkPassToFinal() {
            if (!confirm('Pass ' + this.selectedIds.length + ' evidence files to final report?')) return;
            const res = await fetch('{{ route("evidence.bulk-pass-to-final", $project) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ evidence_ids: this.selectedIds })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Bulk operation failed');
            }
        },

        async bulkCreateRisks() {
            if (!confirm('Auto-create risks for ' + this.selectedIds.length + ' evidence files?')) return;
            const res = await fetch('{{ route("evidence.bulk-create-risks", $project) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ evidence_ids: this.selectedIds })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Bulk operation failed');
            }
        }
    };
}
</script>
@endpush
@endsection
