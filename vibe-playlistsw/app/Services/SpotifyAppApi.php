<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SpotifyAppApi
{
    protected function appToken(): string
    {
        return Cache::remember('spotify_app_token', 3300, function () {
            $resp = Http::asForm()
                ->withBasicAuth(config('services.spotify.client_id'), config('services.spotify.client_secret'))
                ->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'client_credentials',
                ])
                ->throw()
                ->json();

            return $resp['access_token'];
        });
    }

    protected function http()
    {
        return Http::withToken($this->appToken())
            ->baseUrl('https://api.spotify.com/v1');
    }

    public function track(string $id): array
    {
        return $this->http()->get("/tracks/{$id}")->throw()->json();
    }

    public function audioFeatures(string $id): array
    {
        return $this->http()->get("/audio-features/{$id}")->throw()->json();
    }

    public function recommendations(array $params): array
    {
        return $this->http()->get('/recommendations', $params)->throw()->json();
    }
}
