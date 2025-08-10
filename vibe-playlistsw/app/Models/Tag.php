<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name','type','slug'];

    public function songs() { return $this->belongsToMany(Song::class); }
}
