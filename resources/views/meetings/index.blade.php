@extends('layouts.app')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        .glass-premium {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.04);
        }
        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
<div class="p-8 min-h-screen" x-data="meetingsDashboard()" x-cloak>
    
    {{-- Top Heading & Action Button --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-10 gap-6">
        <div class="flex items-center space-x-6">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-2xl">
                <i class="fas fa-calendar-alt text-2xl"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <a href="{{ route('projects.index') }}" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-indigo-600 transition-colors">Projects</a>
                    <i class="fas fa-chevron-right text-[8px] text-slate-300"></i>
                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Meeting Hub</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">{{ $project->name }} - Meetings</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('evidence.show', $project) }}" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all flex items-center shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Workspace
            </a>
            <button @click="showScheduleModal = true" class="px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white shadow-xl hover:bg-indigo-700 transition-all">
                <i class="fas fa-plus mr-2"></i> Schedule Meeting
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold flex items-center shadow-sm">
            <i class="fas fa-check-circle mr-2 text-emerald-500 text-base"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Meetings Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Meeting List --}}
        <div class="lg:col-span-8 space-y-6">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Planned Consultations & Audits</h2>

            @forelse($meetings as $meeting)
                <div class="glass-premium rounded-2xl p-6 relative overflow-hidden transition-all duration-300 hover:shadow-md" id="meeting-card-{{ $meeting->id }}">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="space-y-3 w-full">
                            {{-- Title, Reschedule, Status --}}
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-xl font-bold text-slate-800">{{ $meeting->title }}</h3>
                                
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                      :class="{
                                          'bg-amber-50 text-amber-700 border border-amber-200': '{{ $meeting->status }}' === 'scheduled',
                                          'bg-emerald-50 text-emerald-700 border border-emerald-200': '{{ $meeting->status }}' === 'completed',
                                          'bg-rose-50 text-rose-700 border border-rose-200': '{{ $meeting->status }}' === 'cancelled'
                                      }"
                                      id="status-badge-{{ $meeting->id }}">
                                    {{ $meeting->status }}
                                </span>

                                @if($meeting->status === 'scheduled')
                                    <button @click="openEditModal({
                                                id: {{ $meeting->id }},
                                                title: '{{ addslashes($meeting->title) }}',
                                                description: '{{ addslashes($meeting->description ?? '') }}',
                                                scheduled_at: '{{ $meeting->scheduled_at->format('Y-m-d\TH:i') }}',
                                                meeting_link: '{{ $meeting->meeting_link ?? '' }}',
                                                attendees: {{ json_encode($meeting->attendees->pluck('id')) }},
                                                manual_emails: '{{ implode(', ', $meeting->additional_emails ?? []) }}'
                                            })"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-black uppercase tracking-wider ml-2 flex items-center transition-colors">
                                        <i class="fas fa-edit mr-1"></i> Reschedule / Edit
                                    </button>
                                @endif
                            </div>

                            {{-- Description --}}
                            @if($meeting->description)
                                <p class="text-sm text-slate-600 leading-relaxed max-w-2xl">{{ $meeting->description }}</p>
                            @else
                                <p class="text-xs italic text-slate-400">No description provided.</p>
                            @endif

                            {{-- Info row --}}
                            <div class="flex flex-wrap items-center gap-y-2 gap-x-6 pt-2 text-xs text-slate-500 border-t border-slate-100">
                                <div class="flex items-center">
                                    <i class="far fa-calendar-alt mr-2 text-indigo-500"></i>
                                    <span>{{ $meeting->scheduled_at->format('M d, Y @ h:i A') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="far fa-user mr-2 text-sky-500"></i>
                                    <span>Organizer: <strong>{{ $meeting->creator->username }}</strong></span>
                                </div>
                                @if($meeting->meeting_link)
                                    <div class="flex items-center">
                                        <i class="fas fa-link mr-2 text-teal-500"></i>
                                        <a href="{{ $meeting->meeting_link }}" target="_blank" class="text-indigo-600 font-semibold hover:underline">
                                            Join Meeting Link
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- Attendees --}}
                            @if($meeting->attendees->count() > 0 || !empty($meeting->additional_emails))
                                <div class="pt-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block mb-2">Attendees & Invited Emails</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($meeting->attendees as $attendee)
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold flex items-center">
                                                <i class="far fa-user-circle mr-1.5 text-indigo-500"></i>
                                                {{ $attendee->username }} ({{ $attendee->email }})
                                            </span>
                                        @endforeach
                                        @foreach($meeting->additional_emails ?? [] as $email)
                                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-xs font-semibold flex items-center">
                                                <i class="far fa-envelope mr-1.5 text-indigo-400"></i>
                                                {{ $email }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex md:flex-col items-stretch gap-2 min-w-[120px] pt-4 md:pt-0 border-t md:border-t-0 border-slate-100">
                            @if($meeting->status === 'scheduled')
                                <button @click="updateStatus({{ $meeting->id }}, 'completed')"
                                        class="px-4 py-2 text-center text-xs font-black uppercase tracking-widest bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition shadow-sm">
                                    <i class="fas fa-check mr-1.5"></i> Complete
                                </button>
                                <button @click="updateStatus({{ $meeting->id }}, 'cancelled')"
                                        class="px-4 py-2 text-center text-xs font-black uppercase tracking-widest bg-rose-50 text-rose-600 border border-rose-200 rounded-xl hover:bg-rose-100 transition">
                                    <i class="fas fa-times mr-1.5"></i> Cancel
                                </button>
                            @else
                                <span class="text-xs italic text-slate-400 text-center py-2">No actions available</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="glass-premium rounded-2xl p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto text-slate-400 mb-4">
                        <i class="fas fa-calendar-times text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">No Meetings Scheduled</h3>
                    <p class="text-slate-400 text-sm mt-1 max-w-md mx-auto">Get aligned with compliance experts and team members by scheduling a project meeting.</p>
                </div>
            @endforelse
        </div>

        {{-- Project Members Sidebar --}}
        <div class="lg:col-span-4 space-y-6">
            <div class="glass-premium rounded-2xl p-6">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-4">Workspace Members</h3>
                <div class="space-y-4">
                    @foreach($projectUsers as $user)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 font-bold border border-slate-200">
                                    {{ strtoupper(substr($user->username, 0, 2)) }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">{{ $user->username }}</h4>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                                        {{ $user->roles->pluck('name')->implode(', ') ?: 'Member' }}
                                    </span>
                                </div>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- Schedule Meeting Modal --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
         x-show="showScheduleModal"
         x-transition
         style="display: none;">
        
        <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl border border-slate-100"
             @click.away="showScheduleModal = false">
            
            <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider flex items-center">
                    <i class="fas fa-calendar-plus mr-2.5"></i> Schedule Project Consult
                </h3>
                <button @click="showScheduleModal = false" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form action="{{ route('meetings.store', $project) }}" method="POST" class="p-6 space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Meeting Title</label>
                    <input type="text" name="title" required placeholder="e.g. PCI Scope Verification Session"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Description / Goals</label>
                    <textarea name="description" rows="3" placeholder="Define clear objectives for this call..."
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Scheduled Time</label>
                        <input type="datetime-local" name="scheduled_at" required
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Meeting Link (Zoom/Teams)</label>
                        <input type="url" name="meeting_link" placeholder="https://..."
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Manual Invitee Emails</label>
                    <input type="text" name="manual_emails" placeholder="client@company.com, auditor@agency.com"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                    <p class="text-[10px] text-slate-400 mt-1">Separate multiple email addresses with commas.</p>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1.5">Required Attendees</label>
                    <div class="max-h-36 overflow-y-auto border border-slate-200 rounded-xl p-3 space-y-2 bg-slate-50/50">
                        @foreach($projectUsers as $user)
                            <label class="flex items-center space-x-2 text-sm text-slate-700 cursor-pointer hover:text-indigo-600 transition-colors">
                                <input type="checkbox" name="attendees[]" value="{{ $user->id }}"
                                       class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                <span>{{ $user->username }} ({{ $user->email }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="showScheduleModal = false"
                            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest text-slate-500 bg-slate-100 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-7 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-lg">
                        Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit / Reschedule Meeting Modal --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
         x-show="showEditModal"
         x-transition
         style="display: none;">
        
        <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl border border-slate-100"
             @click.away="showEditModal = false">
            
            <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider flex items-center">
                    <i class="fas fa-edit mr-2.5"></i> Reschedule / Edit Meeting
                </h3>
                <button @click="showEditModal = false" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form :action="`{{ url('projects') }}/{{ $project->id }}/meetings/${editMeeting.id}`" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Meeting Title</label>
                    <input type="text" name="title" required x-model="editMeeting.title"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Description / Goals</label>
                    <textarea name="description" rows="3" x-model="editMeeting.description"
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Scheduled Time</label>
                        <input type="datetime-local" name="scheduled_at" required x-model="editMeeting.scheduled_at"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Meeting Link (Zoom/Teams)</label>
                        <input type="url" name="meeting_link" x-model="editMeeting.meeting_link"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Manual Invitee Emails</label>
                    <input type="text" name="manual_emails" x-model="editMeeting.manual_emails" placeholder="client@company.com, auditor@agency.com"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-600 transition-colors">
                    <p class="text-[10px] text-slate-400 mt-1">Separate multiple email addresses with commas.</p>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1.5">Required Attendees</label>
                    <div class="max-h-36 overflow-y-auto border border-slate-200 rounded-xl p-3 space-y-2 bg-slate-50/50">
                        @foreach($projectUsers as $user)
                            <label class="flex items-center space-x-2 text-sm text-slate-700 cursor-pointer hover:text-indigo-600 transition-colors">
                                <input type="checkbox" name="attendees[]" value="{{ $user->id }}"
                                       :checked="editMeeting.attendees && editMeeting.attendees.includes({{ $user->id }})"
                                       class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                <span>{{ $user->username }} ({{ $user->email }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="showEditModal = false"
                            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest text-slate-500 bg-slate-100 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-7 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-lg">
                        Save & Reschedule
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function meetingsDashboard() {
    return {
        showScheduleModal: false,
        showEditModal: false,
        editMeeting: {
            id: null,
            title: '',
            description: '',
            scheduled_at: '',
            meeting_link: '',
            attendees: [],
            manual_emails: ''
        },

        openEditModal(meeting) {
            this.editMeeting = meeting;
            this.showEditModal = true;
        },

        updateStatus(meetingId, newStatus) {
            if (!confirm(`Are you sure you want to mark this meeting as ${newStatus}?`)) {
                return;
            }

            fetch(`{{ url('projects') }}/{{ $project->id }}/meetings/${meetingId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update badge dynamically
                    const badge = document.getElementById(`status-badge-${meetingId}`);
                    if (badge) {
                        badge.textContent = newStatus;
                        badge.className = "px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest";
                        if (newStatus === 'completed') {
                            badge.classList.add('bg-emerald-50', 'text-emerald-700', 'border', 'border-emerald-200');
                        } else if (newStatus === 'cancelled') {
                            badge.classList.add('bg-rose-50', 'text-rose-700', 'border', 'border-rose-200');
                        } else {
                            badge.classList.add('bg-amber-50', 'text-amber-700', 'border', 'border-amber-200');
                        }
                    }

                    // Remove action buttons
                    const card = document.getElementById(`meeting-card-${meetingId}`);
                    if (card) {
                        const actionDiv = card.querySelector('div.flex.md\\:flex-col');
                        if (actionDiv) {
                            actionDiv.innerHTML = '<span class="text-xs italic text-slate-400 text-center py-2">No actions available</span>';
                        }
                    }
                } else {
                    alert(data.message || 'Failed to update meeting status.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Something went wrong. Please try again.');
            });
        }
    };
}
</script>
@endpush
@endsection
