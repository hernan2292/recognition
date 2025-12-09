<?php

namespace App\Http\Controllers\Api;

use App\Events\AccessGranted;
use App\Events\SuspiciousDetected;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Camera;
use App\Models\FaceEmbedding;
use App\Models\SuspiciousEvent;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecognitionController extends Controller
{
    protected $faceService;

    public function __construct(FaceRecognitionService $faceService)
    {
        $this->faceService = $faceService;
    }

    public function processFrame(Request $request)
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'frame' => 'required|file|mimes:jpg,jpeg,png',
        ]);

        $camera = Camera::findOrFail($request->camera_id);
        if (!$camera->is_active) {
            return response()->json(['status' => 'inactive']);
        }

        // 1. Get Known Embeddings (Cache this in production!)
        $knownFaces = Cache::remember('known_faces', 60, function () {
            // Join users to get names efficiently
            return FaceEmbedding::with('user:id,name')->get()->map(function ($emb) {
                return [
                    'user_id' => $emb->user_id,
                    'name' => $emb->user->name,
                    'embedding' => $emb->embedding_vector, // Already array due to cast
                ];
            })->toArray();
        });

        // 2. Call Microservice
        $frameContent = file_get_contents($request->file('frame')->path());
        $result = $this->faceService->recognize(
            $frameContent, 
            $knownFaces, 
            $camera->threshold
        );

        if (!$result || !isset($result['faces']) || count($result['faces']) === 0) {
            return response()->json(['status' => 'no_face']);
        }

        // 3. Process each detected face
        $events = [];
        $hasSuspicious = false;

        foreach ($result['faces'] as $face) {
            $snapshotPath = 'snapshots/' . date('Y-m-d') . '/' . Str::random(20) . '.jpg';
            Storage::disk('public')->put($snapshotPath, $frameContent); // Save frame

            if ($face['is_suspect']) {
                $hasSuspicious = true;
                $event = SuspiciousEvent::create([
                    'camera_id' => $camera->id,
                    'snapshot_path' => $snapshotPath,
                    'confidence' => $face['confidence'],
                    'notes' => 'Auto-detected unknown person',
                ]);
                
                broadcast(new SuspiciousDetected($event))->toOthers();
                $events[] = ['type' => 'suspicious', 'data' => $event];
            } else {
                // Known User -> Log Attendance
                // Prevent duplicate logs within X seconds
                $lastLog = Attendance::where('user_id', $face['match'])
                    ->where('created_at', '>', now()->subSeconds(10))
                    ->first();

                if (!$lastLog) {
                    $attendance = Attendance::create([
                        'user_id' => $face['match'],
                        'camera_id' => $camera->id,
                        'type' => $this->determineDirection($camera),
                        'confidence' => $face['confidence'],
                        'snapshot_path' => $snapshotPath,
                    ]);
                    
                    broadcast(new AccessGranted($attendance))->toOthers();
                    $events[] = ['type' => 'access', 'data' => $attendance];
                }
            }
        }

        return response()->json(['status' => 'processed', 'events' => $events]);
    }

    private function determineDirection(Camera $camera)
    {
        if ($camera->direction === 'both') return 'check';
        return $camera->direction; // entry/exit
    }
}
