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
        // if you used the CA bundle workaround, add ->withOptions(['verify' => base_path('cacert.pem')])
        return Http::withToken($this->appToken())
            ->baseUrl('https://api.spotify.com/v1');
    }

    // SpotifyAppApi.php
    public function track(string $id): array
    {
        $resp = $this->http()->get("/tracks/{$id}");
        if ($resp->successful()) return $resp->json();
        if (in_array($resp->status(), [401,403,404])) return [];
        $resp->throw();
    }


    public function audioFeatures(string $id): array
    {
        $resp = $this->http()->get("/audio-features/{$id}");
        if ($resp->successful()) return $resp->json();

        if (in_array($resp->status(), [401, 403, 404])) {
            // Some new apps get gated here — return empty and let the app degrade gracefully
            return [];
        }

        $resp->throw();
    }

    public function recommendations(array $params): array
    {
        $resp = $this->http()->get('/recommendations', $params);
        if ($resp->successful()) return $resp->json();

        if (in_array($resp->status(), [401, 403])) {
            return ['tracks' => []];
        }

        $resp->throw();
    }

    public function artist(string $id): array
    {
        $resp = $this->http()->get("/artists/{$id}");
        if ($resp->successful()) return $resp->json();
        if (in_array($resp->status(), [401, 403, 404])) return [];
        $resp->throw();
    }

    public function artists(array $ids): array
    {
        $resp = $this->http()->get('/artists', ['ids' => implode(',', $ids)]);
        if ($resp->successful()) return $resp->json();
        if (in_array($resp->status(), [401, 403, 404])) return ['artists' => []];
        $resp->throw();
    }

    public function artistTopTracks(string $artistId, string $market = 'US'): array
    {
        $resp = $this->http()->get("/artists/{$artistId}/top-tracks", ['market' => $market]);
        if ($resp->successful()) return $resp->json();
        if (in_array($resp->status(), [401, 403, 404])) return ['tracks' => []];
        $resp->throw();
    }

    public function relatedArtists(string $artistId): array
    {
        $resp = $this->http()->get("/artists/{$artistId}/related-artists");
        if ($resp->successful()) return $resp->json();
        if (in_array($resp->status(), [401, 403, 404])) return ['artists' => []];
        $resp->throw();
    }

    public function playlistTracksPage(string $playlistId, int $limit = 100, int $offset = 0): array
    {
        $resp = $this->http()->get("/playlists/{$playlistId}/tracks", [
            'limit'  => $limit,
            'offset' => $offset,
            'fields' => 'items(track(id,uri,is_local)),total,limit,offset,next',
        ]);

        if ($resp->successful()) return $resp->json();

        if (in_array($resp->status(), [401,403,404])) {
            return ['items' => [], 'total' => 0, 'limit' => $limit, 'offset' => $offset, 'next' => null];
        }

        $resp->throw();
    }

    public function playlistTracksAll(string $playlistId, int $max = 500): array
    {
        $ids = [];
        $offset = 0; $limit = 100;

        while (count($ids) < $max) {
            $page = $this->playlistTracksPage($playlistId, $limit, $offset);
            foreach ($page['items'] ?? [] as $it) {
                $t = $it['track'] ?? null;
                if ($t && !($t['is_local'] ?? false) && !empty($t['id'])) {
                    $ids[] = $t['id'];
                }
            }
            if (empty($page['next'])) break;
            $offset += $limit;
        }

        return array_values(array_unique($ids));
    }

    public function tracks(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        $all = [];

        foreach (array_chunk($ids, 50) as $chunk) {
            $resp = $this->http()->get('/tracks', ['ids' => implode(',', $chunk)]);
            if ($resp->successful()) {
                $all = array_merge($all, $resp->json()['tracks'] ?? []);
                continue;
            }
            if (in_array($resp->status(), [401,403,404])) {
                // degrade gracefully
                continue;
            }
            $resp->throw();
        }

        return $all; // array of track objects (nulls may appear; we’ll guard)
    }

    public function audioFeaturesMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        $map = [];

        foreach (array_chunk($ids, 100) as $chunk) {
            $resp = $this->http()->get('/audio-features', ['ids' => implode(',', $chunk)]);
            if ($resp->successful()) {
                foreach ($resp->json()['audio_features'] ?? [] as $af) {
                    if ($af && isset($af['id'])) {
                        $map[$af['id']] = $af;
                    }
                }
                continue;
            }
            if (in_array($resp->status(), [401,403,404])) {
                // app tokens often get gated here; just return partial/empty
                return $map;
            }
            $resp->throw();
        }

        return $map; // id => features
    }




}
