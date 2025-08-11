<?php

namespace App\Filament\Resources\SongResource\Pages;

use App\Filament\Resources\SongResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSong extends EditRecord
{
    protected static string $resource = SongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        /** @var \App\Models\Song $song */
        $song = $this->record;

        $moodIds = collect($this->form->getState()['moodTags'] ?? []);
        $activityIds = collect($this->form->getState()['activityTags'] ?? []);

        // Keep only mood/activity tags on pivot according to form
        $keepIds = $moodIds->merge($activityIds)->unique()->all();

        // Remove existing mood/activity tags then attach the current selection
        $current = $song->tags()->pluck('tags.id', 'tags.id')->keys();
        $toDetach = Tag::whereIn('id', $current)
            ->whereIn('type', ['mood','activity'])
            ->pluck('id');

        if ($toDetach->isNotEmpty()) {
            $song->tags()->detach($toDetach->all());
        }
        if (!empty($keepIds)) {
            $song->tags()->syncWithoutDetaching($keepIds);
        }
    }
}
