@extends('layouts.app')

@section('content')
<div x-data="evidenceManager()">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Evidence Management</h1>
        <p class="mt-1 text-md text-slate-500">Project: <span class="font-semibold text-slate-600">{{ $project->name }}</span></p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left Column: Requirements & Uploads -->
        <div class="lg:w-2/3 space-y-4">
            @foreach($requirements as $req)
            <div class="bg-white rounded-lg shadow-sm" x-data="{ open: false }">
                <button @click="open = !open" class="flex justify-between items-center w-full text-left p-4">
                    <span class="text-md font-semibold text-slate-800">{{ $req->req_num }}: {{ $req->req_description }}</span>
                    <i :class="{'transform rotate-180': open}" class="fas fa-chevron-down text-slate-500 transition-transform"></i>
                </button>
                <div x-show="open" x-transition class="p-4 border-t border-slate-200">
                    <!-- Uploaded Files List -->
                    <h4 class="font-semibold text-slate-600 mb-2">Uploaded Evidence:</h4>
                    @if($evidenceByRequirement->has($req->id))
                        <ul class="space-y-2 mb-4">
                        @foreach($evidenceByRequirement->get($req->id) as $file)
                            <li class="flex items-center justify-between p-2 bg-slate-50 rounded-md">
                                <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="text-sky-600 hover:underline truncate">
                                    <i class="fas fa-file-alt mr-2"></i>{{ $file->original_filename }}
                                </a>
                                <span class="text-xs text-slate-500 ml-4 whitespace-nowrap">by {{ $file->user->username }}</span>
                            </li>
                        @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-slate-500 mb-4">No evidence has been uploaded for this requirement yet.</p>
                    @endif

                    <!-- File Upload Form -->
                    <form action="{{ route('evidence.upload', $project) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="requirement_id" value="{{ $req->id }}">
                        <div class="flex items-center">
                            <input type="file" name="file" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100"/>
                            <button type="submit" class="ml-4 px-4 py-2 bg-sky-500 text-white text-sm font-semibold rounded-md hover:bg-sky-600">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Right Column: Chat -->
        <div class="lg:w-1/3">
            <div class="sticky top-8 bg-white rounded-lg shadow-sm flex flex-col h-[calc(100vh-6rem)]">
                <div class="p-4 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-800">Project Chat</h3>
                </div>
                <!-- Chat Messages -->
                <div class="flex-1 p-4 overflow-y-auto" x-ref="chatbox">
                    <template x-for="message in messages" :key="message.id">
                        <div class="flex mb-4" :class="message.user_id === {{ auth()->id() }} ? 'justify-end' : 'justify-start'">
                            <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg" :class="message.user_id === {{ auth()->id() }} ? 'bg-sky-500 text-white' : 'bg-slate-200 text-slate-800'">
                                <p class="text-sm" x-text="message.message"></p>
                                <p class="text-xs mt-1 opacity-75" x-text="`${message.user.username} (${message.user.roles[0].name}) - ${new Date(message.created_at).toLocaleTimeString()}`"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <!-- Chat Input -->
                <div class="p-4 border-t border-slate-200">
                    <form @submit.prevent="sendMessage">
                        <div class="flex items-center">
                            <input type="text" x-model="newMessage" placeholder="Type your message..." class="w-full border-slate-300 rounded-md shadow-sm focus:ring-sky-500 focus:border-sky-500">
                            <button type="submit" class="ml-2 px-4 py-2 bg-sky-500 text-white rounded-md hover:bg-sky-600"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function evidenceManager() {
        return {
            messages: @json($chatMessages),
            newMessage: '',
            init() {
                this.scrollToBottom();
                // Poll for new messages every 5 seconds
                setInterval(() => {
                    this.fetchMessages();
                }, 5000);
            },
            fetchMessages() {
                fetch('{{ route('evidence.chat.get', $project) }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.length !== this.messages.length) {
                            this.messages = data;
                            this.scrollToBottom();
                        }
                    });
            },
            sendMessage() {
                if (this.newMessage.trim() === '') return;
                
                fetch('{{ route('evidence.chat.post', $project) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message: this.newMessage })
                })
                .then(response => response.json())
                .then(data => {
                    this.messages.push(data);
                    this.newMessage = '';
                    this.scrollToBottom();
                });
            },
            scrollToBottom() {
                this.$nextTick(() => {
                    this.$refs.chatbox.scrollTop = this.$refs.chatbox.scrollHeight;
                });
            }
        }
    }
</script>
@endsection
