<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceEmbedding extends Model
{
    protected $guarded = [];

    protected $casts = [
        'embedding_vector' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
