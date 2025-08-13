<?php

namespace App\Filament\Resources\PlaylistResource\Widgets;

use App\Models\Playlist;
use Filament\Widgets\Widget;

class PlaylistImportProgress extends Widget
{
    // Blade view path (see step 2)
    protected static string $view = 'filament.resources.playlist-resource.playlist-import-progress';

    // Auto-re-render every 2s so progress updates live
    protected static ?string $pollingInterval = '2s';

    // Filament will inject the current record when used on a Resource page
    public ?Playlist $record = null;

    protected function getViewData(): array
    {
        // If Filament didn’t inject it for some reason, try resolving by route key (extra safety on dev)
        $p = $this->record;
        if (!$p) {
            $key = request()->route('record'); // can be id or model depending on Filament internals
            if (is_numeric($key)) {
                $p = Playlist::find($key);
            } elseif ($key instanceof Playlist) {
                $p = $key;
            }
        }

        if (!$p) {
            return [
                'status'  => null,
                'done'    => 0,
                'total'   => 0,
                'percent' => null,
            ];
        }

        $done    = (int) $p->import_done;
        $total   = (int) ($p->import_total ?? 0);
        $status  = $p->import_status;
        $percent = $total > 0 ? (int) floor(($done / max($total, 1)) * 100) : null;

        return compact('status', 'done', 'total', 'percent');
    }
}
