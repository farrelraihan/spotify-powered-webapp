<?php

namespace App\Jobs;

use App\Models\Playlist;
use App\Services\SongIngestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IngestTrackToPlaylistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60; // each track is quick

    public function __construct(
        public int $playlistId,
        public string $spotifyTrackId,
        public int $position,
        public int $userId = 0,
    ) {}

    public function handle(SongIngestService $svc): void
    {
        $playlist = \App\Models\Playlist::find($this->playlistId);
        if (! $playlist) return;

        // Ingest & attach
        $song = $svc->ingest($this->spotifyTrackId, $this->userId);
        if (! $song) return;

        $playlist->songs()->syncWithoutDetaching([
            $song->id => ['position' => $this->position],
        ]);

        // ✅ Atomic increment in DB (no stale in-memory value)
        \App\Models\Playlist::whereKey($playlist->id)->increment('import_done');

        // ✅ Flip to "done" when counters match (pure SQL, no race)
        \App\Models\Playlist::whereKey($playlist->id)
            ->whereNotNull('import_total')
            ->whereColumn('import_done', '>=', 'import_total')
            ->update(['import_status' => 'done']);
    }

}

