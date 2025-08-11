<?php

namespace App\Filament\Resources\PlaylistResource\RelationManagers;

use App\Models\Song;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SongsRelationManager extends RelationManager
{
    protected static string $relationship = 'songs';
    protected static ?string $recordTitleAttribute = 'name';

    // public function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Forms\Components\TextInput::make('name')
    //                 ->required()
    //                 ->maxLength(255),
    //         ]);
    // }

    public function table(Table $table): Table
    {
        return $table
            // Always show songs ordered by pivot position:
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')->square()->label('Cover'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('artists_string')->label('Artists')->wrap(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ]);
}
}
