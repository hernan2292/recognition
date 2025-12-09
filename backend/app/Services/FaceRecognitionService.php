<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    protected $baseUrl;

    public function __construct()
    {
        // Defined in docker-compose environment or .env
        $this->baseUrl = env('PYTHON_SERVICE_URL', 'http://face-service:5000');
    }

    public function health()
    {
        return Http::get("{$this->baseUrl}/health")->json();
    }

    public function extractEmbedding($imageFile)
    {
        $response = Http::attach(
            'file', file_get_contents($imageFile), 'image.jpg'
        )->post("{$this->baseUrl}/extract-embedding");

        if ($response->failed()) {
            throw new \Exception("Failed to extract embedding: " . $response->body());
        }

        return $response->json();
    }

    public function recognize($imageFileContent, $knownEmbeddings, $threshold = 0.45)
    {
        // $knownEmbeddings should be array of ['user_id' => 1, 'embedding' => [...], 'name' => 'John']
        
        $response = Http::attach(
            'file', $imageFileContent, 'frame.jpg'
        )->post("{$this->baseUrl}/recognize", [
            'known_embeddings' => json_encode($knownEmbeddings),
            'threshold' => $threshold,
        ]);

        if ($response->failed()) {
            Log::error("Recognition failed: " . $response->body());
            return null;
        }

        return $response->json();
    }
}
