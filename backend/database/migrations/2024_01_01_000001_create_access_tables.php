<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cameras Table (Points of Access)
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('stream_url');
            $table->string('location')->nullable();
            $table->enum('direction', ['entry', 'exit', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->float('threshold')->default(0.45); // Camera specific threshold
            $table->timestamps();
        });

        // Face Embeddings (User has many embeddings)
        Schema::create('face_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('embedding_vector'); // Storing vector as JSON array
            $table->timestamps();
        });

        // Attendance / Access Logs
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('camera_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['entry', 'exit', 'check']);
            $table->float('confidence');
            $table->string('snapshot_path')->nullable(); // Path to saved image
            $table->timestamps();
        });

        // Suspicious Events
        Schema::create('suspicious_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained()->onDelete('cascade');
            $table->string('snapshot_path')->nullable();
            $table->float('confidence')->nullable(); // Low confidence score if any
            $table->text('notes')->nullable(); // "Indagar" notes
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspicious_events');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('face_embeddings');
        Schema::dropIfExists('cameras');
    }
};
