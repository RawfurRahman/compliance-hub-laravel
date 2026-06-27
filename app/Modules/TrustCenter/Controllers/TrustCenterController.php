<?php

namespace App\Modules\TrustCenter\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\TrustCenterAccessGrantedMail;
use App\Models\ProjectAssessment;
use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use App\Modules\TrustCenter\Models\TrustCenterQuestionnaire;
use App\Modules\TrustCenter\Models\TrustCenterVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TrustCenterController extends Controller
{
    public function index()
    {
        $trustCenters = TrustCenter::with('project')->latest()->get();

        return view('admin.trust-centers.index', compact('trustCenters'));
    }

    public function edit(TrustCenter $trustCenter)
    {
        $trustCenter->load('project');

        $assessments = ProjectAssessment::where('project_id', $trustCenter->project_id)
            ->with('framework')
            ->get();

        return view('admin.trust-centers.edit', compact('trustCenter', 'assessments'));
    }

    public function update(Request $request, TrustCenter $trustCenter)
    {
        $data = $request->validate([
            'headline'      => 'required|string|max:255',
            'summary'       => 'required|string',
            'contact_email' => 'nullable|email|max:255',
            'is_published'  => 'boolean',
        ]);

        $trustCenter->update([
            'headline'      => $data['headline'],
            'summary'       => $data['summary'],
            'contact_email' => $data['contact_email'] ?? null,
            'is_published'  => $request->boolean('is_published'),
        ]);

        $frameworkVisibility = $request->input('framework_visibility', []);
        if (is_array($frameworkVisibility)) {
            ProjectAssessment::where('project_id', $trustCenter->project_id)
                ->whereIn('id', array_keys(array_filter($frameworkVisibility)))
                ->update(['is_publicly_visible' => true]);

            ProjectAssessment::where('project_id', $trustCenter->project_id)
                ->whereNotIn('id', array_keys(array_filter($frameworkVisibility)))
                ->update(['is_publicly_visible' => false]);
        }

        return redirect()->route('admin.trust-centers.edit', $trustCenter)
            ->with('success', 'Trust Center updated successfully.');
    }

    public function requests(TrustCenter $trustCenter)
    {
        $requests = $trustCenter->accessRequests()
            ->with('reviewer')
            ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 WHEN status = 'Approved' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(20);

        return view('admin.trust-centers.requests', compact('trustCenter', 'requests'));
    }

    public function approve(TrustCenter $trustCenter, TrustCenterAccessRequest $accessRequest)
    {
        abort_if($accessRequest->trust_center_id !== $trustCenter->id, 404);

        $accessRequest->update([
            'status'             => 'Approved',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at'        => now(),
        ]);

        Mail::to($accessRequest->requester_email)
            ->send(new TrustCenterAccessGrantedMail($trustCenter, $accessRequest));

        return redirect()->route('admin.trust-centers.requests', $trustCenter)
            ->with('success', 'Request approved. Requester has been notified.');
    }

    public function deny(TrustCenter $trustCenter, TrustCenterAccessRequest $accessRequest)
    {
        abort_if($accessRequest->trust_center_id !== $trustCenter->id, 404);

        $accessRequest->update([
            'status'             => 'Denied',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at'        => now(),
        ]);

        return redirect()->route('admin.trust-centers.requests', $trustCenter)
            ->with('success', 'Request denied.');
    }

    public function questionnaires(TrustCenter $trustCenter)
    {
        $questionnaires = $trustCenter->questionnaires()
            ->orderBy('submitted_at', 'desc')
            ->paginate(20);

        return view('admin.trust-centers.questionnaires', compact('trustCenter', 'questionnaires'));
    }

    public function markResponded(TrustCenter $trustCenter, TrustCenterQuestionnaire $questionnaire)
    {
        abort_if($questionnaire->trust_center_id !== $trustCenter->id, 404);

        $questionnaire->update([
            'status'       => 'Responded',
            'responded_at' => now(),
        ]);

        return redirect()->route('admin.trust-centers.questionnaires', $trustCenter)
            ->with('success', 'Questionnaire marked as responded.');
    }

    public function overview(Request $request, TrustCenter $trustCenter)
    {
        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $visits = TrustCenterVisit::where('trust_center_id', $trustCenter->id)
            ->whereBetween('visited_at', [$from, \Carbon\Carbon::parse($to)->endOfDay()])
            ->selectRaw("DATE(visited_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartData = $visits->map(fn($v) => ['date' => $v->date, 'count' => $v->count]);

        $pendingRequests = $trustCenter->accessRequests()->where('status', 'Pending')->count();

        $unrespondedQuestionnaires = $trustCenter->questionnaires()
            ->where('status', '!=', 'Responded')
            ->count();

        return view('admin.trust-centers.overview', compact(
            'trustCenter', 'chartData', 'pendingRequests',
            'unrespondedQuestionnaires', 'from', 'to'
        ));
    }
}
