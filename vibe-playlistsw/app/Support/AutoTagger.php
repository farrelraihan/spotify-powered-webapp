<?php

namespace App\Support;

class AutoTagger
{
    public static function suggest(array $feat): array
    {
        $val = $feat['valence'] ?? null;
        $eng = $feat['energy'] ?? null;
        $dan = $feat['danceability'] ?? null;

        // Mood
        $mood = 'Neutral';
        if ($val !== null && $eng !== null) {
            if ($val >= 0.6 && $eng >= 0.6) $mood = 'Happy';
            elseif ($val < 0.4 && $eng < 0.4) $mood = 'Chill';
            elseif ($eng >= 0.75) $mood = 'Hype';
        }

        // Activity
        $activity = 'Anytime';
        if ($eng !== null && $dan !== null) {
            if ($eng >= 0.7 && $dan >= 0.6) $activity = 'Workout';
            elseif (($val ?? 1) <= 0.4 && $eng <= 0.5) $activity = 'Study';
            elseif ($dan >= 0.7) $activity = 'Party';
        }

        return [$mood, $activity];
    }
}
