<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PciDssRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EvidenceController extends Controller
{
    /**
     * Display the evidence management page for a specific project.
     */
    public function show(Project $project)
    {
        // This can be adapted for other modules later.
        $requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);
        $project->load('evidenceFiles.user', 'chatMessages.user.roles');

        $evidenceByRequirement = $project->evidenceFiles->groupBy('pci_dss_requirement_id');

        return view('evidence.show', [
            'project' => $project,
            'requirements' => $requirements,
            'evidenceByRequirement' => $evidenceByRequirement,
            'chatMessages' => $project->chatMessages
        ]);
    }

    /**
     * Handle the file upload process and trigger n8n workflow.
     */
    public function upload(Request $request, Project $project)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // Max 20MB
            'requirement_id' => 'required|exists:pci_dss_requirements,id',
        ]);

        $file = $request->file('file');
        $path = $file->store("evidence/{$project->id}", 'public');

        $evidence = $project->evidenceFiles()->create([
            'pci_dss_requirement_id' => $request->requirement_id,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
        ]);

        // ** n8n INTEGRATION POINT **
        $n8nWebhookUrl = env('N8N_FILE_SCAN_WEBHOOK_URL');
        if ($n8nWebhookUrl) {
            try {
                // We send the public URL of the file for n8n to download and scan
                Http::post($n8nWebhookUrl, [
                    'file_id' => $evidence->id,
                    'file_url' => asset('storage/' . $path),
                    'original_filename' => $evidence->original_filename,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to trigger n8n file scan workflow: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'File uploaded successfully and is being scanned.');
    }

    /**
     * Fetch the latest chat messages for a project (for real-time polling).
     */
    public function getMessages(Project $project)
    {
        $messages = $project->chatMessages()->with('user.roles')->latest()->take(50)->get()->reverse();
        return response()->json($messages);
    }

    /**
     * Store a new chat message.
     */
    public function postMessage(Request $request, Project $project)
    {
        $request->validate(['message' => 'required|string']);

        $message = $project->chatMessages()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);
        
        $message->load('user.roles');

        return response()->json($message);
    }
    
    /**
     * API endpoint for n8n to fetch unread messages older than 5 minutes.
     */
    public function getUnreadMessages()
    {
        $messages = \App\Models\ChatMessage::whereNull('read_at')
            ->where('created_at', '<=', Carbon::now()->subMinutes(5))
            ->with('user', 'project.user') // Eager load relationships needed for the notification
            ->get();
            
        // Mark messages as read to prevent re-sending notifications
        foreach($messages as $message) {
            $message->update(['read_at' => now()]);
        }

        return response()->json($messages);
    }
}
