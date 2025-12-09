<div class="max-w-4xl mx-auto p-6 bg-gray-800 rounded-lg text-white">
    <h2 class="text-2xl font-bold mb-6">Register New Personnel</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Form Fields -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-400">Full Name</label>
                <input type="text" wire:model="name" class="w-full bg-gray-900 border border-gray-700 rounded p-2 focus:ring-blue-500 focus:border-blue-500">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-400">Email</label>
                <input type="email" wire:model="email" class="w-full bg-gray-900 border border-gray-700 rounded p-2">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400">Password</label>
                <input type="password" wire:model="password" class="w-full bg-gray-900 border border-gray-700 rounded p-2">
            </div>

            <div class="pt-4">
                <button wire:click="save" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded transition">
                    Create User & Save Biometrics
                </button>
            </div>
        </div>

        <!-- Biometric Capture -->
        <div class="flex flex-col items-center space-y-4" x-data="cameraHandler()">
            <div class="relative w-full aspect-video bg-black rounded overflow-hidden border border-gray-600">
                <video x-ref="video" autoplay class="w-full h-full object-cover"></video>
                <canvas x-ref="canvas" class="hidden"></canvas>
            </div>
            
            <div class="flex gap-2 w-full">
                <button @click="startCamera()" class="flex-1 bg-green-700 hover:bg-green-600 py-2 rounded text-sm">Start Camera</button>
                <button @click="captureAndAnalyze()" class="flex-1 bg-yellow-600 hover:bg-yellow-500 py-2 rounded text-sm">Capture & Analyze</button>
            </div>

            <!-- Captured Samples List -->
            <div class="w-full">
                <h3 class="text-sm font-semibold text-gray-400 mb-2">Valid Samples: <span x-text="samples.length"></span></h3>
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <template x-for="(sample, index) in samples" :key="index">
                        <div class="w-16 h-16 shrink-0 relative">
                            <img :src="sample.image" class="w-full h-full object-cover rounded border border-green-500">
                            <span class="absolute top-0 right-0 bg-green-500 text-black text-[10px] px-1">OK</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- AlpineJS Logic for Camera & API Call -->
    <script>
        function cameraHandler() {
            return {
                stream: null,
                samples: [],
                
                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        this.$refs.video.srcObject = this.stream;
                    } catch(e) {
                        alert('Error accessing camera');
                    }
                },

                captureAndAnalyze() {
                    const canvas = this.$refs.canvas;
                    const video = this.$refs.video;
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    
                    canvas.toBlob(blob => {
                        this.uploadForAnalysis(blob);
                    }, 'image/jpeg');
                },

                async uploadForAnalysis(blob) {
                    const formData = new FormData();
                    formData.append('file', blob);

                    // Call Python Service directly or via Laravel Proxy
                    // Here we assume a proxy route in Laravel for CORS reasons
                    // Route: POST /api/extract-embedding-proxy
                    
                    try {
                        let response = await fetch('/api/proxy/extract-embedding', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        let result = await response.json();
                        
                        if (result.embedding) {
                            // Add to Livewire
                            this.samples.push({ image: URL.createObjectURL(blob) });
                            @this.addEmbedding(result.embedding);
                            alert('Face captured successfully!');
                        } else {
                            alert('No face detected or poor quality.');
                        }
                    } catch(e) {
                        console.error(e);
                        alert('Error processing image');
                    }
                }
            }
        }
    </script>
</div>
