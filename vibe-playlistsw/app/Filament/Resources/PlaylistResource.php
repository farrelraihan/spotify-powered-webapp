<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaylistResource\Pages;
use App\Filament\Resources\PlaylistResource\RelationManagers;
use App\Models\Playlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlaylistResource extends Resource
{
    protected static ?string $model = Playlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

     protected static ?string $navigationLabel = 'Playlists';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Toggle::make('is_public')->label('Public'),
                Forms\Components\Textarea::make('description')->rows(3),

                Forms\Components\Hidden::make('user_id')->default(fn () => auth()->id()),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->columns([
                    Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                    Tables\Columns\IconColumn::make('is_public')->boolean()->label('Public'),
                    Tables\Columns\TextColumn::make('songs_count')->counts('songs')->label('# Songs'),
                    Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->label('Created'),
                    Tables\Columns\TextColumn::make('import_status')
                    ->label('Import')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'running' => 'warning',
                        'queued'  => 'gray',
                        'done'    => 'success',
                        'failed'  => 'danger',
                        default   => 'gray',
                    }),
                    Tables\Columns\TextColumn::make('import_done')
                        ->label('Done')
                        ->formatStateUsing(fn ($state, $record) =>
                            $record->import_total ? "{$state} / {$record->import_total}" : ($state ?: '—')
                        )

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\PlaylistResource\RelationManagers\SongsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaylists::route('/'),
            'create' => Pages\CreatePlaylist::route('/create'),
            'edit' => Pages\EditPlaylist::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = \App\Models\Playlist::query();
        $user = auth()->user();

        if ($user && !($user->is_admin ?? false)) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}
