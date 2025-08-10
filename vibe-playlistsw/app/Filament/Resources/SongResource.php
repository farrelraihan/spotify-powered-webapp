<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SongResource\Pages;
use App\Filament\Resources\SongResource\RelationManagers;
use App\Models\Song;
use App\Services\SongIngestService;
use App\Services\SpotifyAppApi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class SongResource extends Resource
{
    protected static ?string $model = Song::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack'; // need to change later

    protected static ?string $navigationLabel = 'Songs';

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             //
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')->label('Cover')->square(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('artists_string')->label('Artists')->wrap(),
                Tables\Columns\TextColumn::make('valence')->numeric(2)->label('Val'),
                Tables\Columns\TextColumn::make('energy')->numeric(2)->label('Eng'),
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
                        app(SongIngestService::class)->ingest($data['url'], auth()->id());
                        Notification::make()->title('Song added')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                 Tables\Actions\Action::make('openSpotify')
                    ->label('Open')
                    ->icon('heroicon-o-play')
                    ->url(fn (Song $r) => $r->spotify_url, true),

                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Song $r) => $r->name.' — '.$r->artists_string)
                    ->modalContent(fn (Song $r) => view('filament.songs.preview', ['song' => $r])),

                Tables\Actions\Action::make('recommend')
                    ->label('Recommend')
                    ->icon('heroicon-o-sparkles')
                    ->modalHeading('Recommendations')
                    ->action(function (Song $r) {
                        $api = app(\App\Services\SpotifyAppApi::class);
                        $recs = $api->recommendations([
                            'seed_tracks'    => $r->spotify_id,
                            'limit'          => 10,
                            'target_valence' => $r->valence,
                            'target_energy'  => $r->energy,
                        ]);
                        session()->flash('recs', $recs['tracks'] ?? []);
                    })
                    ->modalContent(fn () => view('filament.songs.recs', ['tracks' => session('recs', [])])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSongs::route('/'),
            'create' => Pages\CreateSong::route('/create'),
            'edit' => Pages\EditSong::route('/{record}/edit'),
        ];
    }
}
