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

        // 1) Track metadata
        $meta = $this->spotify->track($trackId);

        // 2) Artist genres (batch up to 3 artists)
        $artistIds = collect($meta['artists'] ?? [])->pluck('id')->filter()->take(3)->values()->all();
        $artistGenres = [];
        if (!empty($artistIds)) {
            $artistsResp = $this->spotify->artists($artistIds);
            $artistGenres = collect($artistsResp['artists'] ?? [])
                ->pluck('genres')
                ->flatten()
                ->map(fn ($g) => mb_strtolower($g))
                ->unique()
                ->take(5)
                ->values()
                ->all();
        }

        // 3) Audio features (may be empty if gated)
        $feat = $this->spotify->audioFeatures($trackId) ?: [];

        // 4) Upsert song
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

        // 5) Attach GENRE tags from artists
        foreach ($artistGenres as $g) {
            $name = ucwords($g);
            $slug = Str::slug('genre-' . $g);
            $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name, 'type' => 'genre']);
            $song->tags()->syncWithoutDetaching($tag->id);
        }

        // 6) Suggest mood & activity (features first; fallback to genres)
        [$mood, $activity] = AutoTagger::suggest($feat, $artistGenres);

        // Remove existing mood/activity tags so we don't keep Neutral/Anytime
        $oldIds = $song->tags()
            ->whereIn('type', ['mood', 'activity'])
            ->pluck('tags.id')
            ->all();
        if (!empty($oldIds)) {
            $song->tags()->detach($oldIds);
        }

        // Attach the fresh mood/activity
        foreach ([$mood => 'mood', $activity => 'activity'] as $name => $type) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($type . '-' . $name)],
                ['name' => $name, 'type' => $type]
            );
            $song->tags()->syncWithoutDetaching($tag->id);
        }

        return $song;
    }

    public function ingestMany(array $trackIds, ?int $userId = null): int
{
    $trackIds = array_values(array_unique(array_filter($trackIds)));
    if (empty($trackIds)) return 0;

    // Batch fetch
    $tracks = $this->spotify->tracks($trackIds);                   // array of track objects
    $featuresById = $this->spotify->audioFeaturesMany($trackIds);  // id => features (may be empty)

    // Build meta map (id => track), collect artist ids
    $metaById = [];
    $artistIds = [];
    foreach ($tracks as $t) {
        if (!$t || empty($t['id'])) continue;
        $metaById[$t['id']] = $t;
        foreach ($t['artists'] ?? [] as $a) {
            if (!empty($a['id'])) $artistIds[] = $a['id'];
        }
    }
    $artistIds = array_values(array_unique($artistIds));

    // Batch artist lookups -> genres
    $genresByArtist = [];
    foreach (array_chunk($artistIds, 50) as $chunk) {
        $resp = $this->spotify->artists($chunk);
        foreach ($resp['artists'] ?? [] as $a) {
            $genresByArtist[$a['id']] = array_map('mb_strtolower', $a['genres'] ?? []);
        }
    }

    $count = 0;

    foreach ($trackIds as $tid) {
        $meta = $metaById[$tid] ?? null;
        if (!$meta) continue;

        // Gather artist genres for this track (max 5)
        $artistGenres = [];
        foreach ($meta['artists'] ?? [] as $a) {
            $artistGenres = array_merge($artistGenres, $genresByArtist[$a['id']] ?? []);
        }
        $artistGenres = collect($artistGenres)->unique()->take(5)->values()->all();

        $feat = $featuresById[$tid] ?? [];

        // Upsert song
        $song = \App\Models\Song::updateOrCreate(
            ['spotify_id' => $tid],
            [
                'name'            => $meta['name'] ?? null,
                'artists_json'    => collect($meta['artists'] ?? [])->pluck('name')->values()->all(),
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

        // (Re)tag genres
        foreach ($artistGenres as $g) {
            $name = ucwords($g);
            $slug = \Illuminate\Support\Str::slug('genre-'.$g);
            $tag = \App\Models\Tag::firstOrCreate(['slug' => $slug], ['name'=>$name,'type'=>'genre']);
            $song->tags()->syncWithoutDetaching($tag->id);
        }

        // Retag mood/activity (clear old)
        $oldIds = $song->tags()->whereIn('type', ['mood','activity'])->pluck('tags.id')->all();
        if (!empty($oldIds)) $song->tags()->detach($oldIds);

        [$mood, $activity] = \App\Support\AutoTagger::suggest($feat, $artistGenres);

        foreach ([$mood => 'mood', $activity => 'activity'] as $name => $type) {
            $tag = \App\Models\Tag::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($type.'-'.$name)],
                ['name'=>$name,'type'=>$type]
            );
            $song->tags()->syncWithoutDetaching($tag->id);
        }

        $count++;
    }

    return $count;
}



}
