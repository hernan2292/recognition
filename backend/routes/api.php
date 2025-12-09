<?php

use App\Http\Controllers\Api\RecognitionController;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/process-frame', [RecognitionController::class, 'processFrame']);

// Proxy to Python Service for Registration (called from frontend)
Route::post('/proxy/extract-embedding', function (Request $request, FaceRecognitionService $service) {
    if (!$request->hasFile('file')) {
        return response()->json(['error' => 'No file provided'], 400);
    }
    
    try {
        $result = $service->extractEmbedding($request->file('file')->path());
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
