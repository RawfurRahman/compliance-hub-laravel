<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvidenceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// n8n Webhook Callback Routes (no CSRF protection by default in api.php)
Route::post('/n8n/scan-callback', [EvidenceController::class, 'n8nFileScanCallback']);
Route::post('/n8n/ai-callback', [EvidenceController::class, 'n8nAiAnalysisCallback']);
Route::post('/n8n/send-email', [EvidenceController::class, 'sendAiAnalysisMail']);
Route::get('/evidence/file/{id}', [EvidenceController::class, 'getFile']);

// @see app/Modules/RiskManagement/Routes/api.php for RMM Control Mapping API routes
