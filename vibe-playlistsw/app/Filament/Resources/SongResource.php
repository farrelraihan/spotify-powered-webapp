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
                    ->modalHeading('Recommendations')
                    ->modalSubmitAction(false)            // no Submit button
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (Song $r) {
                        $api = app(\App\Services\SpotifyAppApi::class);

                        // Try Spotify recommendations
                        $recs = $api->recommendations([
                            'seed_tracks'    => $r->spotify_id,
                            'limit'          => 10,
                            'target_valence' => $r->valence,
                            'target_energy'  => $r->energy,
                        ]);
                        $tracks = $recs['tracks'] ?? [];

                        // Fallback 1: artist's top tracks (multi-market)
                        if (empty($tracks)) {
                            $meta = $api->track($r->spotify_id);   // may return [] if 404
                            $artistId = $meta['artists'][0]['id'] ?? null;

                            if ($artistId) {
                                foreach (['US','GB','JP','DE','ID'] as $m) {
                                    $top = $api->artistTopTracks($artistId, $m);
                                    if (!empty($top['tracks'])) { $tracks = $top['tracks']; break; }
                                }
                            }
                        }

                        // Fallback 2: related artists' top tracks
                        if (empty($tracks ?? []) && !empty($artistId ?? null)) {
                            $rel = $api->relatedArtists($artistId);
                            foreach (array_slice($rel['artists'] ?? [], 0, 3) as $a) {
                                $top = $api->artistTopTracks($a['id'], 'US');
                                foreach (array_slice($top['tracks'] ?? [], 0, 3) as $t) {
                                    $tracks[] = $t;
                                    if (count($tracks) >= 10) break 2;
                                }
                            }
                        }

                        return view('filament.songs.recs', ['tracks' => $tracks]);
                    }),

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
