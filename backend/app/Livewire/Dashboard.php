<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Camera;
use App\Models\Attendance;
use App\Models\SuspiciousEvent;

class Dashboard extends Component
{
    public $cameras;
    public $recentEvents = [];
    public $suspiciousAlerts = [];

    public function mount()
    {
        $this->cameras = Camera::where('is_active', true)->get();
        $this->recentEvents = Attendance::with('user', 'camera')->latest()->take(5)->get();
        $this->suspiciousAlerts = SuspiciousEvent::with('camera')->where('resolved', false)->latest()->take(5)->get();
    }

    public function getListeners()
    {
        return [
            "echo:access-channel,.access.granted" => 'handleAccessGranted',
            "echo:security-channel,.suspicious.detected" => 'handleSuspiciousDetected',
        ];
    }

    public function handleAccessGranted($event)
    {
        $this->recentEvents->prepend($event['attendance']); // In real app, re-fetch or format correctly
        // Limit to 5
        if ($this->recentEvents->count() > 5) $this->recentEvents->pop();
    }

    public function handleSuspiciousDetected($event)
    {
        $this->suspiciousAlerts->prepend($event['event']);
        $this->dispatch('play-alert-sound'); // AlpineJS listener
    }

    public function resolveAlert($id)
    {
        $alert = SuspiciousEvent::find($id);
        if ($alert) {
            $alert->update(['resolved' => true]);
            $this->suspiciousAlerts = $this->suspiciousAlerts->reject(fn($a) => $a->id == $id);
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
