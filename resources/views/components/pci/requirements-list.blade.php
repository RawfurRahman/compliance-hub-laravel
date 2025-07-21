{{-- The @props directive has been removed to allow all public properties from the component class to be available in this view. --}}

<div x-data="{ searchQuery: '' }">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-xl font-bold text-slate-800" id="requirements-list">Part II: PCI DSS Requirements</h2>

            <!-- Dynamic Search Input -->
            <div class="relative mt-4">
                <label for="req-search" class="sr-only">Search Requirements</label>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fas fa-search text-slate-400"></i>
                </div>
                <input 
                    type="search" 
                    id="req-search" 
                    x-model.debounce.300ms="searchQuery" 
                    placeholder="Search requirements by number or keyword..." 
                    class="block w-full rounded-md border-slate-300 py-2 pl-10 pr-10 shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                <div x-show="searchQuery.trim() !== ''" x-transition class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <button type="button" @click="searchQuery = ''" class="text-slate-400 hover:text-slate-600" aria-label="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="p-6 space-y-4">
            {{-- This check verifies if the requirements were loaded from the database. --}}
            @if($requirements->isNotEmpty())
                @foreach($requirements as $req)
                    @php
                        $currentFinding = $findings->get($req->id);
                    @endphp
                    <div 
                        class="bg-slate-50 border border-slate-200 rounded-lg" 
                        x-data="{ open: false, currentFindingData: {{ json_encode($currentFinding ?? new \stdClass()) }} }"
                        x-show="searchQuery.trim() === '' || 
                                '{{ strtolower($req->req_num) }}'.includes(searchQuery.toLowerCase()) || 
                                @json(strtolower($req->req_description)).includes(searchQuery.toLowerCase())"
                        x-transition
                    >
                        <button type="button" @click="open = !open" class="flex justify-between items-center w-full text-left p-4">
                            <span class="text-md font-semibold text-slate-800">{{ $req->req_num }}: {{ $req->req_description }}</span>
                            <i :class="{'transform rotate-180': open}" class="fas fa-chevron-down text-slate-500 transition-transform"></i>
                        </button>
                        <div x-show="open" x-transition class="p-4 border-t border-slate-200">
                            {{-- The rest of the requirement details display remains the same --}}
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Assessment Findings</h3>
                            <table class="min-w-full divide-y divide-gray-300 assessment-table">
                                <thead>
                                    <tr>
                                        <th colspan="4" class="bg-teal-600 text-white">Assessment Findings (select one)</th>
                                        <th colspan="2" class="bg-purple-600 text-white">Select If Below Method(s) Was Used</th>
                                    </tr>
                                    <tr>
                                        <th>In Place</th><th>Not Applicable</th><th>Not Tested</th><th>Not in Place</th>
                                        <th>Compensating Control</th><th>Customized Approach</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr>
                                        @foreach(['In Place', 'Not Applicable', 'Not Tested', 'Not in Place'] as $option)
                                        <td class="text-center">
                                            <div x-show="!isEditing">
                                                @if(optional($currentFinding)->assessment_finding === $option) <i class="fas fa-check-circle text-green-500"></i> @else <i class="fas fa-circle text-gray-300"></i> @endif
                                            </div>
                                            <div x-show="isEditing"><input type="radio" name="findings[{{ $req->id }}][assessment_finding]" value="{{ $option }}" x-model="currentFindingData.assessment_finding"></div>
                                        </td>
                                        @endforeach
                                        <td class="text-center">
                                            <div x-show="!isEditing">
                                                @if(optional($currentFinding)->compensating_control) <i class="fas fa-check-square text-green-500"></i> @else <i class="fas fa-square text-gray-300"></i> @endif
                                            </div>
                                            <div x-show="isEditing"><input type="checkbox" name="findings[{{ $req->id }}][compensating_control]" value="1" x-model="currentFindingData.compensating_control"></div>
                                        </td>
                                        <td class="text-center">
                                            <div x-show="!isEditing">
                                                @if(optional($currentFinding)->customized_approach) <i class="fas fa-check-square text-green-500"></i> @else <i class="fas fa-square text-gray-300"></i> @endif
                                            </div>
                                            <div x-show="isEditing"><input type="checkbox" name="findings[{{ $req->id }}][customized_approach]" value="1" x-model="currentFindingData.customized_approach"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                {{-- This message will be displayed if no requirements are found. --}}
                <div class="text-center py-12 px-6 bg-slate-50 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-5xl text-amber-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-slate-700">No PCI DSS Requirements Found</h3>
                    <p class="text-slate-500 mt-2 max-w-md mx-auto">
                        The list of requirements could not be loaded from the database. This usually means the initial data has not been added yet.
                        <br><br>
                        Please run the database seeder command from your project's terminal:
                    </p>
                    <code class="mt-4 inline-block bg-slate-200 text-slate-800 px-4 py-2 rounded-lg text-sm font-mono">php artisan db:seed</code>
                </div>
            @endif
        </div>
    </div>
</div>
