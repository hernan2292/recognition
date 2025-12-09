<?php

namespace App\Events;

use App\Models\SuspiciousEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuspiciousDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $event;

    public function __construct(SuspiciousEvent $event)
    {
        $this->event = $event;
    }

    public function broadcastOn()
    {
        return new Channel('security-channel');
    }
    
    public function broadcastAs()
    {
        return 'suspicious.detected';
    }
}
