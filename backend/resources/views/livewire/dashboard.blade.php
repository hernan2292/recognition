<div class="p-6 bg-gray-900 min-h-screen text-white">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-blue-400">Control Center <span class="text-gray-500 text-lg">Live</span></h1>
        <div class="bg-gray-800 px-4 py-2 rounded-lg border border-gray-700">
            <span class="text-green-400 font-mono">SYSTEM STATUS: ONLINE</span>
        </div>
    </div>

    <!-- Active Cameras Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($cameras as $camera)
            <div class="bg-black rounded-xl overflow-hidden border border-gray-700 relative group shadow-2xl">
                <div class="absolute top-2 left-2 z-10 bg-red-600 text-white text-xs px-2 py-1 rounded animate-pulse">LIVE</div>
                <div class="absolute top-2 right-2 z-10 bg-gray-900/80 text-white text-xs px-2 py-1 rounded">{{ $camera->name }}</div>
                
                <!-- Simulated Stream / Real Stream URL -->
                <!-- Use img tag for MJPEG or JS player for HLS -->
                <div class="aspect-video bg-gray-800 flex items-center justify-center">
                    @if(Str::startsWith($camera->stream_url, 'http'))
                         <img src="{{ $camera->stream_url }}" class="w-full h-full object-cover" onerror="this.src='/placeholder_camera.jpg'">
                    @else
                        <span class="text-gray-500">Connecting to RTSP...</span>
                    @endif
                </div>

                <div class="p-4 bg-gray-800 border-t border-gray-700 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-400">Location</p>
                        <p class="font-semibold">{{ $camera->location }}</p>
                    </div>
                    <button class="bg-blue-600 hover:bg-blue-500 px-3 py-1 rounded text-sm transition" wire:click="$dispatch('open-camera-modal', {id: {{ $camera->id }}})">
                        Details
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Suspicious Activity Panel -->
        <div class="bg-gray-800 rounded-xl p-6 border border-red-900/30">
            <h2 class="text-xl font-bold mb-4 text-red-400 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                SECURITY ALERTS
            </h2>
            <div class="space-y-4">
                @forelse($suspiciousAlerts as $alert)
                    <div class="bg-gray-900 p-4 rounded-lg flex gap-4 border-l-4 border-red-600 animate-slide-in-right">
                        @if($alert['snapshot_path'])
                            <img src="{{ Storage::url($alert['snapshot_path']) }}" class="w-16 h-16 rounded object-cover border border-gray-700">
                        @else
                            <div class="w-16 h-16 bg-gray-700 rounded flex items-center justify-center">?</div>
                        @endif
                        <div class="flex-1">
                            <h3 class="font-bold text-red-500">Unknown Person Detected</h3>
                            <p class="text-xs text-gray-400">{{ $alert['camera']['name'] ?? 'Unknown Camera' }} • {{ \Carbon\Carbon::parse($alert['created_at'])->diffForHumans() }}</p>
                            <p class="text-sm mt-1 text-gray-300">Conf: {{ number_format(($alert['confidence'] ?? 0) * 100, 1) }}%</p>
                        </div>
                        <div class="flex flex-col justify-center gap-2">
                             <button wire:click="resolveAlert({{ $alert['id'] }})" class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded">Dismiss</button>
                             <button class="text-xs bg-red-600 hover:bg-red-500 px-3 py-1 rounded">Alert Guard</button>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 italic text-center py-4">No active security alerts.</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Access Logs -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h2 class="text-xl font-bold mb-4 text-green-400 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Recent Access
            </h2>
            <div class="space-y-3">
                 @foreach($recentEvents as $event)
                    <div class="flex items-center justify-between p-3 bg-gray-900 rounded-lg border-l-4 border-green-500">
                        <div class="flex items-center gap-3">
                             <div class="w-10 h-10 rounded-full bg-blue-900 flex items-center justify-center text-blue-200 font-bold">
                                 {{ substr($event['user']['name'] ?? 'U', 0, 1) }}
                             </div>
                             <div>
                                 <p class="font-bold text-white">{{ $event['user']['name'] ?? 'Unknown' }}</p>
                                 <p class="text-xs text-gray-400">{{ $event['camera']['name'] ?? 'Gate' }} • {{ $event['type'] }}</p>
                             </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-mono text-gray-500">{{ \Carbon\Carbon::parse($event['created_at'])->format('H:i:s') }}</span>
                        </div>
                    </div>
                 @endforeach
            </div>
        </div>
    </div>

    <!-- Alarm Audio -->
    <audio id="alarm-sound" src="/sounds/alarm.mp3" preload="auto"></audio>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('play-alert-sound', () => {
                const audio = document.getElementById('alarm-sound');
                if(audio) audio.play().catch(e => console.log('Audio blocked'));
            });
        });
    </script>
</div>
