<?php

namespace App\Filament\Resources\PlaylistResource\RelationManagers;

use App\Models\Song;
use App\Services\SongIngestService;
use App\Services\SpotifyAppApi;
use App\Support\ParsesSpotifyUrls;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class SongsRelationManager extends RelationManager
{
    use ParsesSpotifyUrls;

    protected static string $relationship = 'songs';
    protected static ?string $recordTitleAttribute = 'name';

    protected function getTableQuery(): Builder
    {
        return $this->getOwnerRecord()
            ->songs()
            ->withPivot('position')
            ->orderBy('playlist_song.position')
            ->getQuery();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')->label('Cover')->square(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('artists_string')->label('Artists')->wrap(),
                Tables\Columns\TextColumn::make('pivot.position')->label('Pos')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()->preloadRecordSelect(),

                Tables\Actions\Action::make('importSpotify')
                    ->label('Import from Spotify Playlist')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->form([
                        Forms\Components\TextInput::make('url')
                            ->placeholder('https://open.spotify.com/playlist/...')
                            ->required(),
                        Forms\Components\Toggle::make('clearFirst')
                            ->label('Clear existing before import')
                            ->default(false),
                        Forms\Components\TextInput::make('max')
                            ->numeric()->minValue(1)->maxValue(1000)->default(200)
                            ->label('Max tracks to import'),
                    ])
                    ->action(function (array $data) {
                        /** @var \App\Models\Playlist $playlist */
                        $playlist = $this->getOwnerRecord();

                         @set_time_limit(180);

                        $id = $this->parsePlaylistId($data['url']);
                        if (! $id) {
                            Notification::make()->title('Could not parse playlist URL')->warning()->send();
                            return;
                        }

                        $api = app(SpotifyAppApi::class);
                        $trackIds = $api->playlistTracksAll($id, (int) $data['max']);

                        if (empty($trackIds)) {
                            Notification::make()
                                ->title('Spotify did not return tracks (playlist endpoint may be gated).')
                                ->warning()->send();
                            return;
                        }

                        $svc = app(SongIngestService::class);

                        if (!empty($data['clearFirst'])) {
                            $playlist->songs()->detach();
                        }

                        $pos = (int) ($playlist->songs()->max('playlist_song.position') ?? 0);
                        $added = 0;

                       // ✅ Chunk the import to keep each loop fast
                    foreach (array_chunk($trackIds, 25) as $chunk) { // try 25 - 40
                        $ingested = $svc->ingestMany($chunk, auth()->id()); // batch ingest
                        // Attach in the same order:
                        foreach ($chunk as $tid) {
                            $song = \App\Models\Song::where('spotify_id', $tid)->first();
                            if ($song) {
                                $playlist->songs()->syncWithoutDetaching([$song->id => ['position' => ++$pos]]);
                                $added++;
                            }
                        }
                    }

                        Notification::make()->title("Imported {$added} track(s).")->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('setPosition')
                    ->label('Set position')
                    ->icon('heroicon-o-arrows-up-down') // <-- corrected icon name
                    ->form([
                        Forms\Components\TextInput::make('position')->numeric()->required(),
                    ])
                    ->action(function (array $data, Song $record) {
                        $record->pivot->position = (int) $data['position'];
                        $record->pivot->save();
                    }),
                Tables\Actions\DetachAction::make(),
            ]);
    }
}
