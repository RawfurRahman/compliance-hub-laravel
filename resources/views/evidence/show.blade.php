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
                <div x-show="open" x-transition class="p-4 border-t border-slat
