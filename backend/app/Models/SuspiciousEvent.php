<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuspiciousEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'resolved' => 'boolean',
    ];

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }
}
