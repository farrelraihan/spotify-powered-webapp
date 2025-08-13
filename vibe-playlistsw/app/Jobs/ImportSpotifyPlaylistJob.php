<?php

namespace App\Jobs;

use App\Models\Playlist;
use App\Services\SongIngestService;
use App\Services\SpotifyAppApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportSpotifyPlaylistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120; // seconds

    public function __construct(
        public int $playlistId,
        public string $spotifyPlaylistId,
        public int $max = 200,
        public bool $clearFirst = false,
        public int $userId = 0,
    ) {}

    public function handle(SpotifyAppApi $api): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::find($this->playlistId);
        if (! $playlist) return;

        $playlist->update([
            'import_status' => 'running',
            'import_done'   => 0,
            'import_total'  => null,
        ]);

        // Fetch track IDs from Spotify (tolerant)
        $trackIds = $api->playlistTracksAll($this->spotifyPlaylistId, $this->max);

        if (empty($trackIds)) {
            // Likely gated playlist endpoint – fail gracefully
            $playlist->update([
                'import_status' => 'failed',
                'import_total'  => 0,
                'import_done'   => 0,
            ]);
            return;
        }

        if ($this->clearFirst) {
            $playlist->songs()->detach();
        }

        $playlist->update([
            'import_total' => count($trackIds),
            'import_done'  => 0,
        ]);

        // Continue from current max position
        $pos = (int) ($playlist->songs()->max('playlist_song.position') ?? 0);

        foreach ($trackIds as $tid) {
            $pos++;
            IngestTrackToPlaylistJob::dispatch(
                playlistId: $this->playlistId,
                spotifyTrackId: $tid,
                position: $pos,
                userId: $this->userId,
            );
        }

        // Parent job marks status as queued; children will advance import_done.
        $playlist->update(['import_status' => 'running']);
    }
}
