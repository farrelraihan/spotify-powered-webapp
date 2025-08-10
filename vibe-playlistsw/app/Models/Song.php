<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $fillable = [
        'spotify_id','name','artists_json','album_name','cover_url',
        'spotify_url','spotify_uri','preview_url','duration_ms',
        'tempo','valence','energy','danceability','acousticness',
        'instrumentalness','loudness','key','mode','time_signature','created_by',
    ];

    protected $casts = ['artists_json' => 'array'];

    public function tags() { return $this->belongsToMany(Tag::class); }

    public function getArtistsStringAttribute(): string
    {
        return collect($this->artists_json)->join(', ');
    }
}
