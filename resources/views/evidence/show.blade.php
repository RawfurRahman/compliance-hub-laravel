{{-- resources/views/evidence/show.blade.php --}}
@extends('layouts.app')

@push('styles')
    {{-- Link to the dedicated stylesheet for this page --}}
    <link href="{{ asset('css/evidence.css') }}" rel="stylesheet">
@endpush

@section('content')
<div x-data="evidenceManager({{ $project->id }})">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Evidence Hub</h1>
            <p class="mt-1 text-md text-slate-500">Project: <span class="font-semibold text-slate-600">{{ $project->name }}</span></p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('projects.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                <i class="fas fa-arrow-left mr-2"></i> Back to Projects
            </a>
        </div>
    </div>

    {{-- Session Messages for Success/Error feedback --}}
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Upload Error!</strong>
            <ul class="mt-3 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left Column: Requirements & Uploads -->
        <div class="lg:w-2/3 space-y-4">
            @forelse ($requirements as $requirement)
                <div class="bg-white rounded-lg shadow-sm border border-slate-200" x-data="{ open: false }" id="requirement-{{ $requirement->id }}">
                    {{-- Requirement Header (Clickable to expand/collapse) --}}
                    <button @click="open = !open" class="flex justify-between items-center w-full text-left p-4 focus:outline-none">
                        <span class="text-lg font-semibold text-slate-800">{{ $requirement->req_num }}: {{ $requirement->req_description }}</span>
                        <i :class="{'transform rotate-180': open}" class="fas fa-chevron-down text-slate-500 transition-transform"></i>
                    </button>
                    {{-- Collapsible Content Area --}}
                    <div x-show="open" x-transition class="p-4 border-t border-slate-200">

                        {{-- File Upload Form --}}
                        <h3 class="text-xl font-semibold text-slate-700 mb-4">Upload Evidence</h3>
                        <form action="{{ route('evidence.upload', $project) }}" method="POST" enctype="multipart/form-data" class="mb-6 p-4 border border-sky-200 rounded-lg bg-sky-50">
                            @csrf
                            <input type="hidden" name="requirement_id" value="{{ $requirement->id }}">
                            <div class="mb-4">
                                <label for="file-{{ $requirement->id }}" class="block text-sm font-medium text-slate-700">Select File (Max 20MB)</label>
                                <input type="file" name="file" id="file-{{ $requirement->id }}" required class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-sky-100 file:text-sky-700 hover:file:bg-sky-200">
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-upload mr-2"></i> Upload & Process
                            </button>
                        </form>

                        {{-- Table of Uploaded Files for this Requirement --}}
                        <h3 class="text-xl font-semibold text-slate-700 mb-4">Uploaded Files</h3>
                        @php
                            $files = $evidenceByRequirement[$requirement->id] ?? collect();
                        @endphp
                        @if ($files->isEmpty())
                            <p class="text-slate-600">No evidence files uploaded for this requirement yet.</p>
                        @else
                            <div class="overflow-x-auto bg-white rounded-lg shadow-inner border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th>File Name</th>
                                            <th>Uploaded By</th>
                                            <th>Date</th>
                                            <th>Scan Status</th>
                                            <th>AI Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-200">
                                        @foreach ($files as $file)
                                            <tr id="evidence-file-{{ $file->id }}">
                                                <td data-label="File Name">
                                                    <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="text-sky-600 hover:text-sky-900 hover:underline">
                                                        {{ $file->original_filename }}
                                                    </a>
                                                </td>
                                                <td data-label="Uploaded By">{{ $file->user->username ?? 'N/A' }}</td>
                                                <td data-label="Date">{{ $file->created_at->format('Y-m-d H:i') }}</td>
                                                <td data-label="Scan Status">
                                                    <span class="status-badge status-{{ $file->scan_status }}">
                                                        {{ Str::replace('_', ' ', Str::upper($file->scan_status)) }}
                                                    </span>
                                                </td>
                                                <td data-label="AI Status">
                                                    <span class="status-badge status-{{ $file->ai_analysis_status }}">
                                                        {{ Str::replace('_', ' ', Str::upper($file->ai_analysis_status)) }}
                                                    </span>
                                                </td>
                                                <td data-label="Actions" class="actions-cell">
                                                    @can('is-auditor')
                                                        @if ($file->ai_analysis_status === 'awaiting_review')
                                                            <button @click="approveAiAnalysis({{ $file->id }})" class="action-btn approve-btn">Approve</button>
                                                            <button @click="rejectAiAnalysis({{ $file->id }})" class="action-btn reject-btn">Reject</button>
                                                        @elseif ($file->ai_analysis_status === 'approved')
                                                            <span class="text-green-700 text-xs">Approved by {{ $file->approvedBy->username ?? 'Auditor' }}</span>
                                                        @elseif ($file->ai_analysis_status === 'rejected')
                                                            <span class="text-red-700 text-xs">Rejected by {{ $file->approvedBy->username ?? 'Auditor' }}</span>
                                                        @endif
                                                    @endcan
                                                </td>
                                            </tr>
                                            {{-- Display AI Observations and Recommendations in a separate row --}}
                                            @if ($file->ai_observations || $file->ai_recommendations)
                                                <tr class="ai-details-row">
                                                    <td colspan="6">
                                                        <div class="ai-details">
                                                            @if ($file->ai_observations)
                                                                <p class="font-semibold text-slate-800">AI Observations:</p>
                                                                <p class="whitespace-pre-wrap">{{ $file->ai_observations }}</p>
                                                            @endif
                                                            @if ($file->ai_recommendations)
                                                                <p class="font-semibold text-slate-800 mt-2">AI Recommendations:</p>
                                                                <p class="whitespace-pre-wrap">{{ $file->ai_recommendations }}</p>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-slate-600 p-4 bg-white rounded-lg shadow-sm border border-slate-200">No PCI DSS requirements found. Please run the seeder.</p>
            @endforelse
        </div>

        <!-- Right Column: Real-time Chat -->
        <div class="lg:w-1/3">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 sticky top-4" x-data="chatApp({{ $project->id }})">
                <h2 class="text-2xl font-semibold text-slate-800 mb-6">Project Chat</h2>

                <div class="chat-box mb-4" x-ref="chatMessages">
                    <template x-for="message in messages" :key="message.id">
                        <div class="message" :class="message.user_id === {{ Auth::id() }} ? 'self' : 'other'">
                            <div class="message-sender">
                                <span x-text="message.user.username"></span>
                                <span class="role-badge" :class="'role-' + (message.user.roles[0] ? message.user.roles[0].name.toLowerCase() : 'default')" x-text="message.user.roles[0] ? message.user.roles[0].name : 'N/A'"></span>
                            </div>
                            <div class="message-content" x-text="message.message"></div>
                            <div class="message-timestamp" x-text="formatTimestamp(message.created_at)"></div>
                        </div>
                    </template>
                </div>

                <form @submit.prevent="postMessage" class="flex items-center">
                    <input type="text" x-model="newMessage" placeholder="Type your message..." class="flex-1 rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 p-2 mr-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700">
                        Send
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Alpine.js and page-specific JavaScript --}}
<script>
    document.addEventListener('alpine:init', () => {
        // Main component data for the page
        Alpine.data('evidenceManager', (projectId) => ({
            // This function handles the auditor's approval of an AI analysis
            async approveAiAnalysis(evidenceFileId) {
                // In a real app, replace confirm() with a styled modal component
                if (!confirm('Are you sure you want to approve this AI analysis?')) return;

                try {
                    const response = await fetch(`/evidence/${evidenceFileId}/approve-ai`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Approval failed.');
                    alert(data.message); // Replace with a success notification
                    location.reload(); // Reload to show the updated status
                } catch (error) {
                    console.error('Error approving AI analysis:', error);
                    alert('Error: ' + error.message); // Replace with an error notification
                }
            },
            // This function handles the auditor's rejection of an AI analysis
            async rejectAiAnalysis(evidenceFileId) {
                if (!confirm('Are you sure you want to reject this AI analysis?')) return;

                try {
                    const response = await fetch(`/evidence/${evidenceFileId}/reject-ai`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Rejection failed.');
                    alert(data.message); // Replace with a success notification
                    location.reload();
                } catch (error) {
                    console.error('Error rejecting AI analysis:', error);
                    alert('Error: ' + error.message); // Replace with an error notification
                }
            }
        }));

        // Alpine.js component specifically for the chat application
        Alpine.data('chatApp', (projectId) => ({
            messages: [],
            newMessage: '',
            projectId: projectId,
            pollingInterval: null,

            init() {
                this.fetchMessages();
                // Poll for new messages every 5 seconds for real-time updates
                this.pollingInterval = setInterval(() => this.fetchMessages(), 5000);
                // Watch for changes to the messages array and automatically scroll to the bottom
                this.$watch('messages', () => this.$nextTick(() => {
                    const chatBox = this.$refs.chatMessages;
                    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
                }));
            },

            // Fetches the latest messages from the server
            async fetchMessages() {
                try {
                    const response = await fetch(`/projects/${this.projectId}/chat/messages`);
                    if (!response.ok) throw new Error('Failed to fetch messages.');
                    this.messages = await response.json();
                } catch (error) {
                    console.error('Error fetching messages:', error);
                }
            },

            // Posts a new message to the server
            async postMessage() {
                if (this.newMessage.trim() === '') return;
                try {
                    const response = await fetch(`/projects/${this.projectId}/chat/messages`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ message: this.newMessage })
                    });
                    if (!response.ok) throw new Error('Failed to send message.');
                    // No need to push the new message here, the next poll will fetch it,
                    // ensuring we have the fully processed data from the server.
                    this.newMessage = '';
                } catch (error) {
                    console.error('Error posting message:', error);
                    alert('Failed to send message.');
                }
            },

            // Formats a timestamp into a more readable string
            formatTimestamp(timestamp) {
                return new Date(timestamp).toLocaleString();
            }
        }));
    });
</script>
@endsection
