@extends('layouts.app')

@push('styles')
<style>
/* ======================================================
   IMPORTER WIZARD — Premium Aesthetics
   ====================================================== */

.import-container {
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    font-family: 'Inter', sans-serif;
}

/* Glassmorphism card wrapper */
.glass-panel {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 24px;
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}

.import-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
    color: #fff;
    padding: 24px 32px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.import-header h1 {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin: 0;
}

/* Progress steps indicator */
.step-indicator {
    display: flex;
    justify-content: space-between;
    padding: 24px 32px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
}

.step-node {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    transition: all 0.3s ease;
}

.step-node.active {
    color: #2563eb;
}

.step-node.completed {
    color: #059669;
}

.step-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    transition: all 0.3s ease;
}

.step-node.active .step-circle {
    border-color: #2563eb;
    background: #2563eb;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
}

.step-node.completed .step-circle {
    border-color: #059669;
    background: #059669;
    color: #fff;
}

/* Drag & Drop Zone */
.dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 48px 32px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dropzone:hover {
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.02);
}

/* Mapping dropdown layout */
.mapping-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 16px 0;
}

.mapping-row {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mapping-label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}

.mapping-select {
    font-size: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 6px 12px;
    outline: none;
    background: #fff;
    width: 220px;
}

/* Excel Sheet row preview */
.preview-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    max-height: 400px;
}

.preview-table {
    border-collapse: collapse;
    font-size: 11px;
    width: 100%;
}

.preview-table th {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 8px 10px;
    font-weight: 700;
    text-align: center;
    color: #475569;
}

.preview-table td {
    border: 1px solid #e2e8f0;
    padding: 6px 10px;
    white-space: nowrap;
}

/* RAG levels badge style */
.score-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 800;
    font-size: 9px;
    text-transform: uppercase;
}

.score-critical { background: #fee2e2; color: #991b1b; }
.score-high { background: #ffedd5; color: #c2410c; }
.score-medium { background: #fef9c3; color: #854d0e; }
.score-low { background: #dcfce7; color: #166534; }

/* Micro-animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in-up {
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
@endpush

@section('content')
<div x-data="workbookImporter()" class="import-container mt-6">
    <div class="glass-panel">
        
        {{-- HEADER --}}
        <div class="import-header">
            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner">
                <i class="fas fa-file-excel"></i>
            </div>
            <div>
                <h1>Spreadsheet Workbook Importer</h1>
                <p class="text-xs text-sky-200 mt-0.5">Exactly reproduce the workbook-to-DB mapping with validation auditing.</p>
            </div>
        </div>

        {{-- STEPS INDICATOR --}}
        <div class="step-indicator">
            <div class="step-node" :class="step === 1 ? 'active' : (step > 1 ? 'completed' : '')">
                <div class="step-circle">1</div>
                <span>Upload File</span>
            </div>
            <div class="step-node" :class="step === 2 ? 'active' : (step > 2 ? 'completed' : '')">
                <div class="step-circle">2</div>
                <span>Map Columns</span>
            </div>
            <div class="step-node" :class="step === 3 ? 'active' : (step > 3 ? 'completed' : '')">
                <div class="step-circle">3</div>
                <span>Dry-Run Validation</span>
            </div>
            <div class="step-node" :class="step === 4 ? 'active' : ''">
                <div class="step-circle">4</div>
                <span>Completed</span>
            </div>
        </div>

        {{-- STEP CONTROLLERS --}}
        <div class="p-8">
            
            {{-- STEP 1: UPLOAD --}}
            <div x-show="step === 1" class="fade-in-up space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Select Spreadsheet File</h3>
                    <p class="text-xs text-slate-500 mt-1">Provide the Excel workbook file containing the 'Risk Register' and 'Control Mapping' sheets.</p>
                </div>

                <div class="dropzone" @click="$refs.fileInput.click()">
                    <input type="file" x-ref="fileInput" class="hidden" @change="handleFileSelect" accept=".xlsx,.xls,.csv">
                    <i class="fas fa-cloud-upload-alt text-4xl text-slate-300 mb-4 block"></i>
                    <p class="text-sm font-semibold text-slate-700" x-text="file ? file.name : 'Drag & drop file here or click to browse'"></p>
                    <p class="text-xs text-slate-400 mt-1">Supports .xlsx, .xls, and .csv files up to 10MB.</p>
                </div>

                <div x-show="uploadError" class="p-3 bg-rose-50 border border-rose-100 rounded-xl text-rose-700 text-xs font-semibold flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span x-text="uploadError"></span>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button @click="uploadFile()" :disabled="!file || uploading" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-lg shadow-blue-500/15 disabled:opacity-50">
                        <span x-text="uploading ? 'Parsing Workbook…' : 'Parse File & Next'"></span>
                    </button>
                </div>
            </div>

            {{-- STEP 2: COLUMN MAPPING --}}
            <div x-show="step === 2" class="fade-in-up space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Confirm Column Mappings</h3>
                    <p class="text-xs text-slate-500 mt-1">Prefilled suggestions from your spreadsheet headers. Review and adjust any mapped columns.</p>
                </div>

                <div class="max-h-[380px] overflow-y-auto pr-2 space-y-3">
                    <template x-for="(mapping, idx) in mappings" :key="idx">
                        <div class="mapping-row">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center font-extrabold text-xs text-slate-500" x-text="mapping.col"></span>
                                <div>
                                    <span class="mapping-label" x-text="mapping.header"></span>
                                    <span class="text-[10px] text-slate-400 block font-mono">Workbook Header</span>
                                </div>
                            </div>
                            <select x-model="mapping.db_field" class="mapping-select">
                                <option value="">-- Skip Column --</option>
                                <template x-for="field in dbFields" :key="field.id">
                                    <option :value="field.id" x-text="field.name" :selected="field.id === mapping.db_field"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                </div>

                <div class="flex justify-between pt-4 border-t border-slate-100">
                    <button @click="step = 1" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider rounded-xl transition">Back</button>
                    <button @click="runDryRun()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-lg">Run Dry-Run Validation</button>
                </div>
            </div>

            {{-- STEP 3: DRY-RUN PREVIEW --}}
            <div x-show="step === 3" class="fade-in-up space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Dry-Run Validation Output</h3>
                    <p class="text-xs text-slate-500 mt-1">Review the dry-run results below. We reconcile formulas and check constraints before committing to the DB.</p>
                </div>

                {{-- Summary stats --}}
                <div class="grid grid-cols-4 gap-4">
                    <div class="p-3 bg-slate-50 border rounded-xl text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Total Rows</span>
                        <span class="text-xl font-bold text-slate-800 block mt-0.5" x-text="validationRows.length"></span>
                    </div>
                    <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-center">
                        <span class="text-[10px] font-bold text-emerald-600 uppercase">Valid Rows</span>
                        <span class="text-xl font-bold text-emerald-800 block mt-0.5" x-text="validationRows.filter(r => r.status === 'Passed').length"></span>
                    </div>
                    <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-center">
                        <span class="text-[10px] font-bold text-amber-600 uppercase">Warnings</span>
                        <span class="text-xl font-bold text-amber-800 block mt-0.5" x-text="validationRows.filter(r => r.status === 'Warning').length"></span>
                    </div>
                    <div class="p-3 bg-rose-50 border border-rose-100 rounded-xl text-center">
                        <span class="text-[10px] font-bold text-rose-600 uppercase">Failed</span>
                        <span class="text-xl font-bold text-rose-800 block mt-0.5" x-text="validationRows.filter(r => r.status === 'Failed').length"></span>
                    </div>
                </div>

                {{-- Excel Table Row Preview --}}
                <div class="preview-table-wrap">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Status</th>
                                <th>Risk ID</th>
                                <th>Asset / Process</th>
                                <th>Risk Owner</th>
                                <th>Inherent Score</th>
                                <th>Residual Score</th>
                                <th>Validation Logs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in validationRows" :key="row.row_index">
                                <tr>
                                    <td class="text-center font-bold text-slate-500" x-text="row.row_index"></td>
                                    <td class="text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold uppercase"
                                              :class="row.status === 'Passed' ? 'bg-emerald-100 text-emerald-800' : (row.status === 'Warning' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                              x-text="row.status"></span>
                                    </td>
                                    <td class="font-mono font-bold" x-text="row.serial_no"></td>
                                    <td x-text="row.asset_process"></td>
                                    <td x-text="row.risk_owner"></td>
                                    <td class="text-center font-bold">
                                        <span class="score-badge" :class="'score-' + row.inherent_level.toLowerCase()" x-text="row.inherent_rating"></span>
                                    </td>
                                    <td class="text-center font-bold">
                                        <span class="score-badge" :class="'score-' + row.residual_level.toLowerCase()" x-text="row.residual_rating"></span>
                                    </td>
                                    <td class="text-xs">
                                        <ul class="list-disc pl-3 text-slate-500 space-y-0.5">
                                            <template x-for="err in row.errors">
                                                <li class="text-rose-600 font-semibold" x-text="err"></li>
                                            </template>
                                            <template x-for="wrn in row.warnings">
                                                <li class="text-amber-600" x-text="wrn"></li>
                                            </template>
                                            <template x-if="row.errors.length === 0 && row.warnings.length === 0">
                                                <li class="text-emerald-600 font-semibold">Ready to import</li>
                                            </template>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between pt-4 border-t border-slate-100">
                    <button @click="step = 2" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold uppercase tracking-wider rounded-xl transition">Back</button>
                    <button @click="confirmImport()" :disabled="validationRows.filter(r => r.status === 'Failed').length > 0 || importing" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-lg disabled:opacity-50">
                        <span x-text="importing ? 'Importing Risks…' : 'Confirm & Commit Import'"></span>
                    </button>
                </div>
            </div>

            {{-- STEP 4: COMPLETED --}}
            <div x-show="step === 4" class="fade-in-up text-center py-8 space-y-6">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-4xl mx-auto shadow-inner">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Workbook Successfully Seeded!</h3>
                    <p class="text-sm text-slate-500 mt-2" x-text="successMessage"></p>
                </div>

                <div class="pt-6 flex justify-center gap-4">
                    <a href="{{ route('risk-register.index', $project) }}" class="px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-md">
                        Go to Risk Register
                    </a>
                    <a href="{{ route('risk-register.heatmap', $project) }}" class="px-6 py-3 bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-md">
                        View Risk Heat Map
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function workbookImporter() {
    return {
        step: 1,
        file: null,
        uploading: false,
        uploadError: '',
        tempFile: '',
        mappings: [],
        validationRows: [],
        importing: false,
        successMessage: '',
        
        dbFields: [
            { id: 'serial_no', name: 'Serial Number (#)' },
            { id: 'asset_process_service', name: 'Asset / Process / Service' },
            { id: 'risk_owner', name: 'Risk Owner' },
            { id: 'risk_calculation_date', name: 'Date' },
            { id: 'asset_value_bdt', name: 'Asset Value (BDT)' },
            { id: 'threats', name: 'Threat' },
            { id: 'threat_level_t', name: 'Threat Level (T)' },
            { id: 'vulnerabilities', name: 'Vulnerability' },
            { id: 'impact_confidentiality', name: 'Impact C' },
            { id: 'impact_integrity', name: 'Impact I' },
            { id: 'impact_availability', name: 'Impact A' },
            { id: 'existing_control', name: 'Existing Control' },
            { id: 'vulnerability_level_av', name: 'Vuln. Level (AV)' },
            { id: 'tv_t_av', name: 'TV (T+AV)' },
            { id: 'likelihood_lh', name: 'Likelihood (LH)' },
            { id: 'risk_rating_avtvlh', name: 'Risk Rating (AV*TV*LH)' },
            { id: 'measurement', name: 'Measurement' },
            { id: 'proposed_control', name: 'Proposed Control' },
            { id: 'communication', name: 'Communication' },
            { id: 'implementation_from', name: 'Impl. From' },
            { id: 'implementation_to', name: 'Impl. To' },
            { id: 'implementation_status', name: 'Impl. Status' },
            { id: 'residual_tv', name: 'Residual TV' },
            { id: 'residual_lh', name: 'Residual LH' },
            { id: 'residual_rating', name: 'Residual Rating' },
            { id: 'follow_up_note', name: 'Follow-up Note' },
        ],

        handleFileSelect(e) {
            this.file = e.target.files[0];
            this.uploadError = '';
        },

        async uploadFile() {
            if (!this.file) return;
            this.uploading = true;
            this.uploadError = '';

            const formData = new FormData();
            formData.append('file', this.file);

            try {
                const response = await fetch("{{ route('risk-register.import.dry-run', $project) }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });
                
                const data = await response.json();
                if (data.success) {
                    this.tempFile = data.temp_file;
                    this.mappings = data.suggested_mappings;
                    this.validationRows = data.validation_rows;
                    this.step = 2;
                } else {
                    this.uploadError = data.error || 'Failed parsing workbook file.';
                }
            } catch (e) {
                this.uploadError = 'Connection error when uploading spreadsheet.';
            } finally {
                this.uploading = false;
            }
        },

        async runDryRun() {
            // Simply transition to step 3 since validation rows were parsed in pre-import stage
            this.step = 3;
        },

        async confirmImport() {
            this.importing = true;
            try {
                const response = await fetch("{{ route('risk-register.import.confirm', $project) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        temp_file: this.tempFile,
                        mappings: this.mappings,
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    this.successMessage = data.message;
                    this.step = 4;
                } else {
                    alert(data.error || 'Failed to complete import process.');
                }
            } catch (e) {
                alert('Connection error when completing database import.');
            } finally {
                this.importing = false;
            }
        }
    };
}
</script>
@endpush
