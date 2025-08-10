<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playlists extends Model
{
    protected $fillable = ['user_id','name','is_public','description','cover_url'];

    public function user() { return $this->belongsTo(User::class); }

    public function songs()
    {
        return $this->belongsToMany(Song::class)
            ->withPivot('position')
            ->orderBy('position');
    }
}
