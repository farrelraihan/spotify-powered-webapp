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
                        ->placeholder('https://open.spotify.com/playlist/... or spotify:playlist:...')
                        ->required(),
                    Forms\Components\Toggle::make('clearFirst')
                        ->label('Clear existing before import')->default(false),
                    Forms\Components\TextInput::make('max')
                        ->numeric()->minValue(1)->maxValue(1000)->default(200)
                        ->label('Max tracks to import'),
                ])
                ->action(function (array $data) {
                    /** @var \App\Models\Playlist $playlist */
                    $playlist = $this->getOwnerRecord();

                    // Parse the playlist ID from URL or URI
                    $url = trim($data['url']);
                    $id = null;
                    if (preg_match('~playlist/([a-zA-Z0-9]+)~', $url, $m)) $id = $m[1];
                    if (!$id && preg_match('~spotify:playlist:([a-zA-Z0-9]+)~', $url, $m)) $id = $m[1];

                    if (!$id) {
                        \Filament\Notifications\Notification::make()
                            ->title('Could not parse playlist URL')
                            ->warning()->send();
                        return;
                    }

                    // Mark as queued & dispatch
                    $playlist->update([
                        'import_status' => 'queued',
                        'import_total'  => null,
                        'import_done'   => 0,
                    ]);

                    \App\Jobs\ImportSpotifyPlaylistJob::dispatch(
                        playlistId: $playlist->id,
                        spotifyPlaylistId: $id,
                        max: (int) ($data['max'] ?? 200),
                        clearFirst: (bool) ($data['clearFirst'] ?? false),
                        userId: auth()->id() ?? 0,
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Import started')
                        ->body('You can leave this page. Progress will update automatically.')
                        ->success()->send();
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
