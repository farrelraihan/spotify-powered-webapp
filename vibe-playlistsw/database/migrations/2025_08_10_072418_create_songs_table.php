<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id')->unique();
            $table->string('name');
            $table->json('artists_json')->nullable();
            $table->string('album_name')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('spotify_url')->nullable();
            $table->string('spotify_uri')->nullable();
            $table->string('preview_url')->nullable();
            $table->integer('duration_ms')->nullable();

            // audio features
            $table->float('tempo')->nullable();
            $table->float('valence')->nullable();
            $table->float('energy')->nullable();
            $table->float('danceability')->nullable();
            $table->float('acousticness')->nullable();
            $table->float('instrumentalness')->nullable();
            $table->float('loudness')->nullable();
            $table->integer('key')->nullable();
            $table->integer('mode')->nullable();
            $table->integer('time_signature')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
