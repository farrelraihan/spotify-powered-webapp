<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $seed = [
            'mood' => ['Happy','Chill','Sad','Hype','Neutral'],
            'activity' => ['Study','Workout','Roadtrip','Sleep','Anytime','Party'],
            'genre' => ['Pop','Lo-Fi','EDM','Jazz','Rock','Hip-Hop'],
        ];
        foreach ($seed as $type => $names) {
            foreach ($names as $name) {
                Tag::firstOrCreate(
                    ['slug' => Str::slug($type.'-'.$name)],
                    ['name' => $name, 'type' => $type]
                );
            }
        }

    }
}
