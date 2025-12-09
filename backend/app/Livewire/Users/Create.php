<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\FaceEmbedding;
use App\Services\FaceRecognitionService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $password;
    public $capturedImages = []; // Stores temporary paths or base64
    public $embeddings = []; // Stores extracted vectors
    
    public $isProcessing = false;

    public function extractEmbedding($imageIndex)
    {
        // In a real scenario, we save the temporary image and send it to the service
        // For this demo, let's assume capturedImages contains DataURLs
        $this->isProcessing = true;
        
        $dataUrl = $this->capturedImages[$imageIndex];
        // Convert to file/tmp
        // Call Service
        // Store embedding result to $this->embeddings[$imageIndex]
        
        // Mocking the call flow:
        // $service = app(FaceRecognitionService::class);
        // $result = $service->extractEmbedding($tmpPath);
        
        $this->isProcessing = false;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);

        // Save embeddings
        // In this Livewire component, we expect the JS to have sent the embeddings or images
        // For simplicity, we assume $this->embeddings is populated via a specific method call from JS
        foreach ($this->embeddings as $emb) {
            FaceEmbedding::create([
                'user_id' => $user->id,
                'embedding_vector' => $emb
            ]);
        }

        session()->flash('message', 'User created and biometrics registered.');
        return redirect()->route('users.index');
    }

    public function addEmbedding($vector)
    {
        $this->embeddings[] = $vector;
    }

    public function render()
    {
        return view('livewire.users.create');
    }
}
