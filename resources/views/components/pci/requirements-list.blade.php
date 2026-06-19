{{-- resources/views/pci/components/requirements-list.blade.php --}}
@props(['requirements', 'findings'])

{{--
    This component displays the list of all PCI DSS requirements.
    - It has its own Alpine.js state (`x-data`) to manage the search functionality independently.
    - `searchQuery` stores the text from the search input.
--}}
<div x-data="{ searchQuery: '' }">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200">
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
            @if($requirements->isNotEmpty())
                @foreach($requirements as $req)
                    @php
                        // Get the finding for the current requirement, if it exists.
                        $currentFinding = $findings->get($req->id);
                    @endphp
                    <div
                        class="bg-slate-50 border border-slate-200 rounded-lg"
                        {{--
                            ** THE FIX IS HERE (Part 1) **
                            We are moving the potentially complex description string out of the `x-show` directive
                            and into the `x-data` scope as a proper JavaScript variable.
                            - `description`: We safely JSON-encode the lowercase version of the requirement description.
                            This is a much safer way to pass server-side data to Alpine.
                        --}}
                        x-data="{
                            open: false,
                            currentFindingData: {{ json_encode($currentFinding ?? ['is_applicable' => true, 'required_documents' => '']) }},
                            description: {{ json_encode(strtolower($req->req_description)) }}
                        }"
                        {{--
                            ** THE FIX IS HERE (Part 2) **
                            The `x-show` directive is now cleaner and safer. It compares the search query
                            against the `description` variable from `x-data` instead of an injected string.
                            This completely prevents any special characters from breaking the JavaScript syntax.
                        --}}
                        x-show="searchQuery.trim() === '' ||
                                '{{ strtolower($req->req_num) }}'.includes(searchQuery.toLowerCase()) ||
                                description.includes(searchQuery.toLowerCase())"
                        x-transition
                    >
                        <button type="button" @click="open = !open" class="flex justify-between items-center w-full text-left p-4">
                            <span class="text-md font-semibold text-slate-800">{{ $req->req_num }}: {{ $req->req_description }}</span>
                            <i :class="{'transform rotate-180': open}" class="fas fa-chevron-down text-slate-500 transition-transform"></i>
                        </button>
                        <div x-show="open" x-transition class="p-4 border-t border-slate-200 space-y-6">
                            
                            {{-- Section for Scope & Curation (Auditor/Admin Only) --}}
                            @canany(['is-admin', 'is-auditor'])
                            <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                                <h3 class="text-lg font-medium text-indigo-900 mb-2">Scope & Curation</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex items-center space-x-3">
                                        <label class="font-medium text-slate-700">Is this requirement applicable to the client?</label>
                                        <div x-show="!isEditing">
                                            <span x-show="currentFindingData.is_applicable !== false" class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Yes</span>
                                            <span x-show="currentFindingData.is_applicable === false" class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">No</span>
                                        </div>
                                        <div x-show="isEditing">
                                            <select name="findings[{{ $req->id }}][is_applicable]" x-model="currentFindingData.is_applicable" class="border-gray-300 rounded-md shadow-sm text-sm">
                                                <option :value="true">Yes, Applicable</option>
                                                <option :value="false">No, Out of Scope</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="font-medium text-slate-700 block mb-1">Required Document List (Visible to Customer)</label>
                                        <div x-show="!isEditing" class="p-2 bg-white rounded border min-h-[40px] text-sm text-slate-600 whitespace-pre-wrap" x-text="currentFindingData.required_documents || 'No specific documents requested.'"></div>
                                        <div x-show="isEditing">
                                            <textarea name="findings[{{ $req->id }}][required_documents]" x-model="currentFindingData.required_documents" rows="2" class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="e.g. Network Diagram, Firewall configuration..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endcanany

                            {{-- Wrapper to hide assessing fields if out of scope --}}
                            <div x-show="currentFindingData.is_applicable !== false" class="space-y-6">

                                {{-- Section for Assessment Findings --}}
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Assessment Findings</h3>
                                    <table class="min-w-full divide-y divide-gray-300 assessment-table">
                                    <thead class="bg-slate-100">
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
                                                <div x-show="!isEditing" class="py-2">
                                                    @if(optional($currentFinding)->assessment_finding === $option) <i class="fas fa-check-circle text-green-500 text-lg"></i> @else <i class="far fa-circle text-gray-300 text-lg"></i> @endif
                                                </div>
                                                <div x-show="isEditing"><input type="radio" name="findings[{{ $req->id }}][assessment_finding]" value="{{ $option }}" x-model="currentFindingData.assessment_finding" class="h-5 w-5 text-sky-600"></div>
                                            </td>
                                            @endforeach
                                            <td class="text-center">
                                                <div x-show="!isEditing" class="py-2">
                                                    @if(optional($currentFinding)->compensating_control) <i class="fas fa-check-square text-purple-500 text-lg"></i> @else <i class="far fa-square text-gray-300 text-lg"></i> @endif
                                                </div>
                                                <div x-show="isEditing"><input type="checkbox" name="findings[{{ $req->id }}][compensating_control]" value="1" x-model="currentFindingData.compensating_control" class="h-5 w-5 text-purple-600 rounded"></div>
                                            </td>
                                            <td class="text-center">
                                                <div x-show="!isEditing" class="py-2">
                                                    @if(optional($currentFinding)->customized_approach) <i class="fas fa-check-square text-purple-500 text-lg"></i> @else <i class="far fa-square text-gray-300 text-lg"></i> @endif
                                                </div>
                                                <div x-show="isEditing"><input type="checkbox" name="findings[{{ $req->id }}][customized_approach]" value="1" x-model="currentFindingData.customized_approach" class="h-5 w-5 text-purple-600 rounded"></div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- ** NEWLY ADDED SECTION ** --}}
                            {{-- Section for Testing Procedures and Assessor Responses --}}
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Testing Procedures & Assessor Responses</h3>
                                <table class="min-w-full divide-y divide-gray-300 assessment-table">
                                    <thead class="bg-slate-100">
                                        <tr>
                                            <th class="w-1/2 text-left">Procedure</th>
                                            <th class="w-1/2 text-left">Assessor's Response</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        {{-- Loop through each testing procedure for the requirement --}}
                                        @foreach($req->testing_procedures as $index => $procedure)
                                            <tr>
                                                <td class="align-top">
                                                    <p class="font-semibold text-slate-700">{{ $procedure['procedure'] }}</p>
                                                    <p class="text-xs text-slate-500 mt-2"><strong>Instruction:</strong> {{ $procedure['instruction'] }}</p>
                                                </td>
                                                <td class="align-top">
                                                    {{-- Display the response in view mode --}}
                                                    <div x-show="!isEditing" class="prose max-w-none p-2 bg-slate-50 rounded-md border min-h-[50px]">
                                                        {{-- Use the null coalescing operator to safely access the response --}}
                                                        {!! nl2br(e(optional($currentFinding)->assessor_responses[$index] ?? '')) !!}
                                                    </div>
                                                    {{-- Show a textarea for the response in edit mode --}}
                                                    <div x-show="isEditing">
                                                        <textarea
                                                            name="findings[{{ $req->id }}][assessor_responses][{{ $index }}]"
                                                            rows="4"
                                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                                            x-model="currentFindingData.assessor_responses[{{ $index }}]"
                                                            placeholder="Enter assessor response..."
                                                        ></textarea>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div> {{-- End of "Applicable constraint" wrapper --}}
                        </div>
                    </div>
                @endforeach
            @else
                {{-- This message is displayed if the requirements seeder has not been run. --}}
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
