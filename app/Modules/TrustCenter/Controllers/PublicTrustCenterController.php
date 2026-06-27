<?php

namespace App\Modules\TrustCenter\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use App\Modules\TrustCenter\Models\TrustCenterQuestionnaire;
use App\Modules\TrustCenter\Models\TrustCenterVisit;
use Illuminate\Http\Request;

class PublicTrustCenterController extends Controller
{
    public function show(string $slug)
    {
        $trustCenter = TrustCenter::where('public_slug', $slug)->first();

        if (!$trustCenter || !$trustCenter->is_published) {
            return response()->view('trust-center.public.not-available', [], 404);
        }

        $trustCenter->load('project');

        $visibleFrameworks = $trustCenter->publicFrameworks()->get();

        $publicEvidence = $trustCenter->publicEvidence()->get(['id', 'original_filename', 'mime_type']);

        $requesterEmail = session('trust_center_requester_email');
        $hasApprovedAccess = false;
        if ($requesterEmail) {
            $hasApprovedAccess = TrustCenterAccessRequest::where('trust_center_id', $trustCenter->id)
                ->where('requester_email', $requesterEmail)
                ->where('status', 'Approved')
                ->exists();
        }

        TrustCenterVisit::create([
            'trust_center_id' => $trustCenter->id,
            'visited_at'      => now(),
            'ip_hash'         => hash('sha256', request()->ip()),
        ]);

        $questions = TrustCenterQuestionnaire::QUESTIONS;

        return view('trust-center.public.show', compact(
            'trustCenter', 'visibleFrameworks', 'publicEvidence', 'hasApprovedAccess', 'questions'
        ));
    }

    public function requestAccess(Request $request, string $slug)
    {
        $trustCenter = TrustCenter::where('public_slug', $slug)->firstOrFail();

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'company' => 'nullable|string|max:255',
            'note'    => 'nullable|string|max:2000',
        ]);

        TrustCenterAccessRequest::create([
            'trust_center_id'  => $trustCenter->id,
            'requester_name'   => $data['name'],
            'requester_email'  => $data['email'],
            'requester_company' => $data['company'] ?? null,
            'note'             => $data['note'] ?? null,
            'status'           => 'Pending',
        ]);

        session(['trust_center_requester_email' => $data['email']]);

        return back()->with('success', 'Your access request has been submitted. We will be in touch.');
    }

    public function submitQuestionnaire(Request $request, string $slug)
    {
        $trustCenter = TrustCenter::where('public_slug', $slug)->firstOrFail();

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'company' => 'nullable|string|max:255',
            'responses' => 'required|array',
        ]);

        $answers = $request->input('responses', []);
        $responses = [];
        foreach (TrustCenterQuestionnaire::QUESTIONS as $q) {
            $responses[] = [
                'key'      => $q['key'],
                'question' => $q['question'],
                'answer'   => $answers[$q['key']] ?? '',
            ];
        }

        TrustCenterQuestionnaire::create([
            'trust_center_id'  => $trustCenter->id,
            'requester_name'   => $data['name'],
            'requester_email'  => $data['email'],
            'requester_company' => $data['company'] ?? null,
            'status'           => 'Submitted',
            'responses'        => $responses,
            'submitted_at'     => now(),
        ]);

        session(['trust_center_requester_email' => $data['email']]);

        return back()->with('success', 'Your questionnaire has been submitted. We will be in touch.');
    }
}
