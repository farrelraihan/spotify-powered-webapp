<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthAccount extends Model
{
    protected $fillable = ['user_id','provider','access_token','refresh_token','expires_at','scopes'];
    protected $casts = ['scopes' => 'array', 'expires_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
