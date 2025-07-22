@extends('layouts.app')

@section('content')
<div x-data="evidenceManager({{ $project->id }})">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Evidence Hub</h1>
            <p class="mt-1 text-md text-slate-500">Project: <span class="font-semibold text-slate-600">{{ $project->name }}</span></p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Whoops!</strong>
            <span class="block sm:inline">There were some problems with your input.</span>
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
                    <button @click="open = !open" class="flex justify-between items-center w-full text-left p-4 focus:outline-none">
                        <span class="text-lg font-semibold text-slate-800">{{ $requirement->req_num }}: {{ $requirement->req_description }}</span>
                        <i :class="{'transform rotate-180': open}" class="fas fa-chevron-down text-slate-500 transition-transform"></i>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-3" x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-3"
                         class="p-4 border-t border-slate-200">

                        <h3 class="text-xl font-semibold text-slate-700 mb-4">Upload Evidence for this Requirement</h3>
                        <form action="{{ route('evidence.upload', $project) }}" method="POST" enctype="multipart/form-data" class="mb-6 p-4 border border-blue-200 rounded-lg bg-blue-50">
                            @csrf
                            <input type="hidden" name="requirement_id" value="{{ $requirement->id }}">
                            <div class="mb-4">
                                <label for="file-{{ $requirement->id }}" class="block text-sm font-medium text-slate-700">Select File (Max 20MB)</label>
                                <input type="file" name="file" id="file-{{ $requirement->id }}" class="mt-1 block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-100 file:text-blue-700
                                    hover:file:bg-blue-200">
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-upload mr-2"></i> Upload Evidence
                            </button>
                        </form>

                        <h3 class="text-xl font-semibold text-slate-700 mb-4">Uploaded Evidence Files ({{ $requirement->req_num }})</h3>
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
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">File Name</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Uploaded By</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Upload Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Scan Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">AI Analysis Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-200">
                                        @foreach ($files as $file)
                                            <tr id="evidence-file-{{ $file->id }}">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                                    <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-900 hover:underline">
                                                        {{ $file->original_filename }}
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $file->user->username ?? 'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $file->created_at->format('Y-m-d H:i') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                                    <span class="status-badge {{ $file->scan_status }}">
                                                        {{ Str::replace('_', ' ', Str::upper($file->scan_status)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                                    <span class="status-badge {{ $file->ai_analysis_status }}">
                                                        {{ Str::replace('_', ' ', Str::upper($file->ai_analysis_status)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    @if ($file->ai_analysis_status === 'awaiting_review' && Auth::user()->hasRole('Auditor'))
                                                        <button @click="approveAiAnalysis({{ $file->id }})" class="text-green-600 hover:text-green-900 mr-2">Approve</button>
                                                        <button @click="rejectAiAnalysis({{ $file->id }})" class="text-red-600 hover:text-red-900">Reject</button>
                                                    @elseif ($file->ai_analysis_status === 'approved')
                                                        <span class="text-green-700 text-xs">Approved by {{ $file->approvedBy->username ?? 'Auditor' }}</span>
                                                    @endif
                                                    {{-- Add delete button if needed --}}
                                                    {{-- <form action="{{ route('evidence.destroy', $file) }}" method="POST" onsubmit="return confirm('Are you sure?')" class="inline-block ml-2">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                    </form> --}}
                                                </td>
                                            </tr>
                                            {{-- Display AI Observations and Recommendations --}}
                                            @if ($file->ai_observations || $file->ai_recommendations)
                                                <tr>
                                                    <td colspan="6" class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                                                        <div class="text-sm text-slate-700">
                                                            @if ($file->ai_observations)
                                                                <p class="font-semibold text-slate-800">AI Observations:</p>
                                                                <p class="whitespace-pre-wrap text-slate-600">{{ $file->ai_observations }}</p>
                                                            @endif
                                                            @if ($file->ai_recommendations)
                                                                <p class="font-semibold text-slate-800 mt-2">AI Recommendations:</p>
                                                                <p class="whitespace-pre-wrap text-slate-600">{{ $file->ai_recommendations }}</p>
                                                            @endif
                                                            @if ($file->ai_analysis_status === 'awaiting_review')
                                                                <p class="text-orange-600 mt-2 font-medium">Awaiting Auditor Review.</p>
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
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6" x-data="chatApp({{ $project->id }})">
                <h2 class="text-2xl font-semibold text-slate-800 mb-6">Project Chat</h2>

                <div class="chat-box mb-4" x-ref="chatMessages">
                    <template x-for="message in messages" :key="message.id">
                        <div class="message" :class="message.user_id === {{ Auth::id() }} ? 'self' : 'other'">
                            <div class="message-sender">
                                <span class="font-semibold" x-text="message.user.username"></span>
                                <span class="text-xs text-slate-500 ml-1" x-text="'(' + (message.user.roles[0] ? message.user.roles[0].name : 'N/A') + ')'"></span>
                            </div>
                            <div x-text="message.message" class="text-slate-700"></div>
                            <div class="message-timestamp" x-text="formatTimestamp(message.created_at)"></div>
                        </div>
                    </template>
                </div>

                <form @submit.prevent="postMessage" class="flex items-center">
                    <input type="text" x-model="newMessage" placeholder="Type your message..." class="flex-1 rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 mr-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Send
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Alpine.js data for chat functionality
    document.addEventListener('alpine:init', () => {
        Alpine.data('evidenceManager', (projectId) => ({
            // This top-level Alpine data can manage global state or functions if needed
            // For now, it primarily wraps the chatApp data.
            // You can add global evidence-related functions here if they don't fit in chatApp.
        }));

        Alpine.data('chatApp', (projectId) => ({
            messages: [],
            newMessage: '',
            projectId: projectId,
            pollingInterval: null,

            init() {
                this.fetchMessages();
                // Poll for new messages every 3 seconds
                this.pollingInterval = setInterval(() => this.fetchMessages(), 3000);
                this.$watch('messages', () => this.$nextTick(() => {
                    const chatBox = this.$refs.chatMessages;
                    if (chatBox) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                }));
            },

            async fetchMessages() {
                try {
                    const response = await fetch(`/projects/${this.projectId}/chat/messages`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    this.messages = await response.json();
                } catch (error) {
                    console.error('Error fetching messages:', error);
                }
            },

            async postMessage() {
                if (this.newMessage.trim() === '') return;

                try {
                    const response = await fetch(`/projects/${this.projectId}/chat/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message: this.newMessage })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const sentMessage = await response.json();
                    this.messages.push(sentMessage); // Add new message to array
                    this.newMessage = ''; // Clear input
                    // Scroll to bottom is handled by watcher
                } catch (error) {
                    console.error('Error posting message:', error);
                    // Use a custom modal/notification system instead of alert in production
                    alert('Failed to send message.');
                }
            },

            formatTimestamp(timestamp) {
                const date = new Date(timestamp);
                return date.toLocaleString(); // Adjust format as needed
            }
        }));
    });

    // JavaScript functions for AI Analysis Approval/Rejection (outside Alpine.js component for global access)
    async function approveAiAnalysis(evidenceFileId) {
        if (!confirm('Are you sure you want to approve this AI analysis?')) { // Replace with custom modal
            return;
        }

        try {
            const response = await fetch(`/evidence/${evidenceFileId}/approve-ai`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            });

            const data = await response.json();
            if (response.ok) {
                alert(data.message); // Replace with custom modal
                location.reload(); // Reload to reflect status change
            } else {
                alert('Error: ' + data.message); // Replace with custom modal
            }
        } catch (error) {
            console.error('Error approving AI analysis:', error);
            alert('Network error during approval.'); // Replace with custom modal
        }
    }

    async function rejectAiAnalysis(evidenceFileId) {
        // Replace with custom modal with text area for notes
        const notes = prompt('Please provide reasons for rejection/edits (optional):'); 

        try {
            const response = await fetch(`/evidence/${evidenceFileId}/reject-ai`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ auditor_notes: notes })
            });

            const data = await response.json();
            if (response.ok) {
                alert(data.message); // Replace with custom modal
                location.reload(); // Reload to reflect status change
            } else {
                alert('Error: ' + data.message); // Replace with custom modal
            }
        } catch (error) {
            console.error('Error rejecting AI analysis:', error);
            alert('Network error during rejection.'); // Replace with custom modal
        }
    }
</script>
@endsection
