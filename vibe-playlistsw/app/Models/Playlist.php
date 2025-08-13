<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Playlist extends Model
{
    protected $fillable = ['user_id','name','is_public','description','cover_url','import_total','import_done','import_status'];

    public function user() { return $this->belongsTo(User::class); }

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class, 'playlist_song')
            ->withPivot('position')
            ->orderBy('playlist_song.position');
    }
}
