<?php

namespace App\Services;

use App\Models\Song;
use App\Models\Tag;
use App\Support\AutoTagger;
use App\Support\ParsesSpotifyUrls;
use Illuminate\Support\Str;

class SongIngestService
{
    use ParsesSpotifyUrls;

    public function __construct(protected SpotifyAppApi $spotify) {}

    public function ingest(string $urlOrId, ?int $userId = null): Song
    {
        $trackId = $this->parseTrackId($urlOrId);
        throw_unless($trackId, \InvalidArgumentException::class, 'Cannot parse Spotify track ID.');

        $meta = $this->spotify->track($trackId);
        $feat = $this->spotify->audioFeatures($trackId);

        $song = Song::updateOrCreate(
            ['spotify_id' => $trackId],
            [
                'name'            => $meta['name'],
                'artists_json'    => collect($meta['artists'])->pluck('name')->values()->all(),
                'album_name'      => $meta['album']['name'] ?? null,
                'cover_url'       => $meta['album']['images'][0]['url'] ?? null,
                'spotify_url'     => $meta['external_urls']['spotify'] ?? null,
                'spotify_uri'     => $meta['uri'] ?? null,
                'preview_url'     => $meta['preview_url'] ?? null,
                'duration_ms'     => $meta['duration_ms'] ?? null,
                'tempo'           => $feat['tempo'] ?? null,
                'valence'         => $feat['valence'] ?? null,
                'energy'          => $feat['energy'] ?? null,
                'danceability'    => $feat['danceability'] ?? null,
                'acousticness'    => $feat['acousticness'] ?? null,
                'instrumentalness'=> $feat['instrumentalness'] ?? null,
                'loudness'        => $feat['loudness'] ?? null,
                'key'             => $feat['key'] ?? null,
                'mode'            => $feat['mode'] ?? null,
                'time_signature'  => $feat['time_signature'] ?? null,
                'created_by'      => $userId,
            ]
        );

        // auto-tag mood + activity
        [$mood, $activity] = AutoTagger::suggest($feat);
        foreach ([$mood => 'mood', $activity => 'activity'] as $name => $type) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($type.'-'.$name)],
                ['name' => $name, 'type' => $type]
            );
            $song->tags()->syncWithoutDetaching($tag->id);
        }

        return $song;
    }
}
