<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SongResource\Pages;
use App\Models\Song;
use App\Models\Tag;
use App\Services\SongIngestService;
use App\Services\SpotifyAppApi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SongResource extends Resource
{
    protected static ?string $model = Song::class;
    protected static ?string $navigationIcon = 'heroicon-o-musical-note';
    protected static ?string $navigationLabel = 'Songs';

    // Ensure Filament always gets a proper Eloquent builder (and eager-load tags)
    public static function getEloquentQuery(): Builder
    {
        return Song::query()->with('tags');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('artists_string')->label('Artists')->disabled()->dehydrated(false),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('moodTags')
                    ->label('Mood')
                    ->multiple()
                    ->preload()
                    ->options(fn() => Tag::query()->where('type', 'mood')->orderBy('name')->pluck('name', 'id'))
                    ->default(fn(?Song $record) => $record?->tags()->where('type', 'mood')->pluck('tags.id')->all() ?? [])
                    ->dehydrated(false),

                Forms\Components\Select::make('activityTags')
                    ->label('Activity')
                    ->multiple()
                    ->preload()
                    ->options(fn() => Tag::query()->where('type', 'activity')->orderBy('name')->pluck('name', 'id'))
                    ->default(fn(?Song $record) => $record?->tags()->where('type', 'activity')->pluck('tags.id')->all() ?? [])
                    ->dehydrated(false),
            ]),
            Forms\Components\TextInput::make('spotify_url')->label('Spotify URL')->disabled()->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')->label('Cover')->square(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('artists_string')->label('Artists')->wrap(),
                Tables\Columns\TextColumn::make('tags_list')
                    ->label('Tags')
                    ->getStateUsing(fn(Song $r) => $r->tags->pluck('name')->join(', '))
                    ->wrap(),
                Tables\Columns\TextColumn::make('valence')->numeric(2)->label('Val'),
                Tables\Columns\TextColumn::make('energy')->numeric(2)->label('Eng'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mood')
                    ->label('Mood')
                    ->relationship('tags', 'name', fn(Builder $query) => $query->where('type', 'mood')),
                Tables\Filters\SelectFilter::make('activity')
                    ->label('Activity')
                    ->relationship('tags', 'name', fn(Builder $query) => $query->where('type', 'activity')),
                Tables\Filters\SelectFilter::make('genre')
                    ->label('Genre')
                    ->relationship('tags', 'name', fn(Builder $query) => $query->where('type', 'genre')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('addByUrl')
                    ->label('Add Song (Spotify URL)')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('url')
                            ->placeholder('https://open.spotify.com/track/... or spotify:track:...')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $song = app(SongIngestService::class)->ingest($data['url'], auth()->id());
                        Notification::make()
                            ->title('Song added' . ($song->valence === null ? ' (tagged via artist genres)' : ''))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('refreshAll')
                    ->label('Refresh all')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function () {
                        $svc = app(SongIngestService::class);
                        $count = 0;
                        foreach (Song::query()->cursor() as $song) {
                            if ($song->spotify_id) {
                                $svc->ingest($song->spotify_id, auth()->id());
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("Refreshed {$count} song(s).")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('refresh')
                    ->label('Refresh')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Song $r) {
                        app(SongIngestService::class)->ingest($r->spotify_id, auth()->id());
                        Notification::make()->title('Refreshed')->success()->send();
                    }),
                Tables\Actions\Action::make('openSpotify')
                    ->label('Open')
                    ->icon('heroicon-o-play')
                    ->url(fn(Song $r) => $r->spotify_url, true),
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn(Song $r) => $r->name . ' — ' . $r->artists_string)
                    ->modalContent(fn(Song $r) => view('filament.songs.preview', ['song' => $r])),
                // in SongResource.php, inside ->actions([...])
                Tables\Actions\Action::make('recommend')
                    ->label('Recommend')
                    ->icon('heroicon-o-sparkles')
                    ->modalHeading('Recommendations (compact)')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (\App\Models\Song $r) {
                        $api    = app(\App\Services\SpotifyAppApi::class);
                        $TARGET = 10;

                        // helpers
                        $uniqByTrackId = function (array $tracks): array {
                            $seen = []; $out = [];
                            foreach ($tracks as $t) {
                                $id = $t['id'] ?? ($t['uri'] ?? null);
                                if (!$id || isset($seen[$id])) continue;
                                $seen[$id] = true;
                                $out[] = $t;
                            }
                            return $out;
                        };
                        $capPerArtist = function (array $tracks, int $cap = 2): array {
                            $count = []; $out = [];
                            foreach ($tracks as $t) {
                                $aid = $t['artists'][0]['id'] ?? ($t['artists'][0]['name'] ?? 'unknown');
                                if (($count[$aid] ?? 0) >= $cap) continue;
                                $count[$aid] = ($count[$aid] ?? 0) + 1;
                                $out[] = $t;
                            }
                            return $out;
                        };

                        // seed
                        $seedMeta       = $api->track($r->spotify_id);
                        $seedArtistId   = $seedMeta['artists'][0]['id']   ?? null;
                        $seedArtistName = $seedMeta['artists'][0]['name'] ?? ($r->artists_json[0] ?? null);

                        $pool = [];

                        // 1) /recommendations
                        $recs = $api->recommendations([
                            'seed_tracks'    => $r->spotify_id,
                            'limit'          => 20,
                            'target_valence' => $r->valence,
                            'target_energy'  => $r->energy,
                        ]);
                        if (!empty($recs['tracks'])) $pool = array_merge($pool, $recs['tracks']);

                        // 2) related artists -> top tracks
                        if ($seedArtistId && count($pool) < $TARGET) {
                            $rel = $api->relatedArtists($seedArtistId);
                            foreach (array_slice($rel['artists'] ?? [], 0, 8) as $a) {
                                $rid = $a['id'] ?? null;
                                if (!$rid || $rid === $seedArtistId) continue;
                                $top = $api->artistTopTracks($rid, 'US');
                                foreach (array_slice($top['tracks'] ?? [], 0, 2) as $t) $pool[] = $t;
                                if (count($pool) >= $TARGET * 2) break;
                            }
                        }

                        // 3) sprinkle seed artist
                        if ($seedArtistId && count($pool) < $TARGET) {
                            foreach (['US','GB','JP','DE','ID'] as $m) {
                                $top = $api->artistTopTracks($seedArtistId, $m);
                                if (!empty($top['tracks'])) {
                                    foreach ($top['tracks'] as $t) $pool[] = $t;
                                    if (count($pool) >= $TARGET * 2) break;
                                }
                            }
                        }

                        // dedupe + cap
                        $pool = $uniqByTrackId($pool);
                        $pool = $capPerArtist($pool, 2);

                        // 4) fill from local library (exclude same primary artist by JSON)
                        if (count($pool) < $TARGET) {
                            $tagIds  = $r->tags()->pluck('tags.id');
                            $primary = $seedArtistName;

                            $local = \App\Models\Song::query()
                                ->whereKeyNot($r->id)
                                ->when($primary, fn ($q) =>
                                    $q->whereRaw('JSON_SEARCH(artists_json, "one", ?) IS NULL', [$primary])
                                )
                                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                                ->withCount(['tags as overlap_count' => fn ($q) => $q->whereIn('tags.id', $tagIds)])
                                ->orderByDesc('overlap_count')
                                ->inRandomOrder()
                                ->limit($TARGET * 2)
                                ->get();

                            foreach ($local as $s) {
                                $pool[] = [
                                    'id'    => $s->spotify_id,
                                    'name'  => $s->name,
                                    'artists' => collect($s->artists_json ?? [])->map(fn ($n) => ['name' => $n])->all(),
                                    'album'   => ['images' => [['url' => $s->cover_url]]],
                                    'external_urls' => ['spotify' => $s->spotify_url],
                                    'preview_url'   => $s->preview_url,
                                ];
                            }
                        }

                        // normalize minimal fields we’ll render
                        $tracks = collect($pool)
                            ->filter(fn ($t) => is_array($t) && !empty($t['name']) && !empty($t['artists']))
                            ->map(function ($t) {
                                $imgs = data_get($t, 'album.images', []);
                                $img  = $imgs[1]['url'] ?? ($imgs[0]['url'] ?? '');
                                return [
                                    'title'   => $t['name'] ?? '—',
                                    'artist'  => collect($t['artists'])->pluck('name')->filter()->join(', ') ?: 'Unknown artist',
                                    'img'     => $img,
                                    'open'    => data_get($t, 'external_urls.spotify', '#'),
                                ];
                            })
                            ->take($TARGET)
                            ->values()
                            ->all();

                        // compact 2-col grid with hard inline sizes (nothing can override)
                        $css = '<style>
                        .rec-grid{display:grid;grid-template-columns:1fr;gap:10px}
                        @media(min-width:768px){.rec-grid{grid-template-columns:1fr 1fr}}
                        .rec-item{display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05)}
                        .rec-thumb{width:56px;height:56px;border-radius:8px;object-fit:cover;flex:0 0 56px}
                        .rec-title{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
                        .rec-artist{font-size:12px;opacity:.75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
                        .rec-open{margin-left:auto;padding:6px 10px;border-radius:8px;background:#22c55e;color:#fff;text-decoration:none;font-size:12px}
                        .rec-open:hover{background:#16a34a}
                        </style>';

                        $html = $css . '<div class="rec-grid">';
                        foreach ($tracks as $t) {
                            $img = $t['img']
                                ? '<img class="rec-thumb" src="'.$t['img'].'" loading="lazy" />'
                                : '<div class="rec-thumb" style="background:rgba(255,255,255,.1)"></div>';
                            $html .= '<div class="rec-item">
                                '.$img.'
                                <div style="min-width:0">
                                    <div class="rec-title">'.$t['title'].'</div>
                                    <div class="rec-artist">'.$t['artist'].'</div>
                                </div>
                                <a class="rec-open" target="_blank" href="'.$t['open'].'">Open</a>
                            </div>';
                        }
                        if (!$tracks) $html .= '<div class="rec-artist">No recs right now.</div>';
                        $html .= '</div>';

                        return new \Illuminate\Support\HtmlString($html);
                    })






            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('refreshSelected')
                    ->label('Refresh selected')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $svc = app(SongIngestService::class);
                        $count = 0;
                        foreach ($records as $song) {
                            if ($song->spotify_id) {
                                $svc->ingest($song->spotify_id, auth()->id());
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("Refreshed {$count} song(s).")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSongs::route('/'),
            'create' => Pages\CreateSong::route('/create'),
            'edit'   => Pages\EditSong::route('/{record}/edit'),
        ];
    }
}
