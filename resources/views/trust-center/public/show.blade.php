@extends('layouts.public')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-16">
    {{-- Headline --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-extrabold text-slate-900 mb-4">{{ $trustCenter->headline }}</h1>
        <p class="text-lg text-slate-600 leading-relaxed">{{ $trustCenter->summary }}</p>
    </div>

    {{-- Frameworks --}}
    @if($visibleFrameworks->count() > 0)
        <div class="mb-12">
            <h2 class="text-xl font-bold text-slate-800 mb-6">Frameworks & Certifications</h2>
            <div class="space-y-3">
                @foreach($visibleFrameworks as $framework)
                    <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800">{{ $framework->name }}</h3>
                            @if($framework->version)
                                <p class="text-sm text-slate-500">Version {{ $framework->version }}</p>
                            @endif
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700">
                                <i class="fas fa-check-circle text-xs"></i> Active
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Evidence Documents --}}
    @if($publicEvidence->count() > 0)
        <div class="mb-12">
            <h2 class="text-xl font-bold text-slate-800 mb-6">Evidence Documents</h2>
            <div class="space-y-3">
                @foreach($publicEvidence as $evidence)
                    <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-sky-50 flex items-center justify-center text-sky-600">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-800">{{ $evidence->original_filename }}</h3>
                            <p class="text-sm text-slate-500">{{ strtoupper($evidence->mime_type ?? 'Unknown') }}</p>
                        </div>
                        <div>
                            @if($hasApprovedAccess)
                                <a href="{{ url('/api/evidence/file/' . $evidence->id) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-white bg-sky-500 hover:bg-sky-600 rounded-xl transition-all"
                                   target="_blank">
                                    <i class="fas fa-download text-xs"></i> Download
                                </a>
                            @else
                                <span class="text-xs text-slate-400 italic">Request access to download</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Request Access --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h2 class="text-xl font-bold text-slate-800 mb-2">Request Access</h2>
        <p class="text-sm text-slate-500 mb-6">
            Interested in learning more? Submit your details and we'll get back to you.
        </p>
        <form action="{{ route('trust-center.public.request-access', $trustCenter->public_slug) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Your Name</label>
                <input type="text" name="name" id="name" required
                       placeholder="John Doe"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all">
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" id="email" required
                       placeholder="you@company.com"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all">
                @error('email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="company" class="block text-sm font-semibold text-slate-700 mb-1">Company <span class="font-normal text-slate-400">(optional)</span></label>
                <input type="text" name="company" id="company"
                       placeholder="Acme Inc."
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all">
                @error('company')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="note" class="block text-sm font-semibold text-slate-700 mb-1">Note <span class="font-normal text-slate-400">(optional)</span></label>
                <textarea name="note" id="note" rows="3"
                          placeholder="Tell us what you're looking for..."
                          class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all"></textarea>
                @error('note')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all">
                <i class="fas fa-paper-plane mr-1.5 text-xs"></i> Submit Request
            </button>
        </form>
    </div>

    {{-- Questionnaire --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm mt-8">
        <h2 class="text-xl font-bold text-slate-800 mb-2">Quick Security Questionnaire</h2>
        <p class="text-sm text-slate-500 mb-6">
            Help us understand your due diligence needs by answering a few common questions.
        </p>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-6">
                <i class="fas fa-check-circle mr-1.5 text-emerald-500"></i>
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('trust-center.public.questionnaire', $trustCenter->public_slug) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="q_name" class="block text-sm font-semibold text-slate-700 mb-1">Your Name</label>
                <input type="text" name="name" id="q_name" required
                       placeholder="John Doe"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all">
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="q_email" class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" id="q_email" required
                       placeholder="you@company.com"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all">
                @error('email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="q_company" class="block text-sm font-semibold text-slate-700 mb-1">Company <span class="font-normal text-slate-400">(optional)</span></label>
                <input type="text" name="company" id="q_company"
                       placeholder="Acme Inc."
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all">
                @error('company')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-slate-200 pt-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Questions</h3>
                @foreach($questions as $q)
                    <div class="mb-4">
                        <label for="q_{{ $q['key'] }}" class="block text-sm font-semibold text-slate-700 mb-1">{{ $q['question'] }}</label>
                        <textarea name="responses[{{ $q['key'] }}]" id="q_{{ $q['key'] }}" rows="2"
                                  placeholder="Your answer..."
                                  class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all"></textarea>
                    </div>
                @endforeach
            </div>

            <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all">
                <i class="fas fa-paper-plane mr-1.5 text-xs"></i> Submit Questionnaire
            </button>
        </form>
    </div>

    {{-- Contact --}}
    @if($trustCenter->contact_email)
        <div class="text-center mt-8">
            <p class="text-sm text-slate-400">
                Questions? Email us at
                <a href="mailto:{{ $trustCenter->contact_email }}" class="text-sky-600 hover:text-sky-700 font-semibold">
                    {{ $trustCenter->contact_email }}
                </a>
            </p>
        </div>
    @endif
</div>
@endsection
