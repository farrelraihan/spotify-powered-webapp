<?php

namespace App\Support;

class AutoTagger
{
public static function suggest(array $features = [], array $genres = []): array
    {
        $val = $features['valence'] ?? null;
        $eng = $features['energy'] ?? null;
        $dan = $features['danceability'] ?? null;

        // 1) Try features first (if available)
        $mood = 'Neutral';
        if ($val !== null && $eng !== null) {
            if ($val >= 0.6 && $eng >= 0.6) $mood = 'Happy';
            elseif ($val < 0.4 && $eng < 0.4) $mood = 'Chill';
            elseif ($eng >= 0.75) $mood = 'Hype';
        }

        $activity = 'Anytime';
        if ($eng !== null && $dan !== null) {
            if ($eng >= 0.7 && $dan >= 0.6) $activity = 'Workout';
            elseif (($val ?? 1) <= 0.4 && $eng <= 0.5) $activity = 'Study';
            elseif ($dan >= 0.7) $activity = 'Party';
        }

        // 2) If features are missing, infer from genres
        if (($features['valence'] ?? null) === null && !empty($genres)) {
            $m = self::moodFromGenres($genres);
            $a = self::activityFromGenres($genres);
            if ($m) $mood = $m;
            if ($a) $activity = $a;
        }

        return [$mood, $activity];
    }

    protected static function moodFromGenres(array $genres): ?string
    {
        $g = self::norm($genres);

    $map = [
        // Chill / mellow
        'lo-fi' => 'Chill', 'lofi' => 'Chill', 'ambient' => 'Chill', 'chillhop' => 'Chill',
        'jazz' => 'Chill', 'classical' => 'Chill', 'acoustic' => 'Chill',
        'indie pop' => 'Chill', 'bedroom pop' => 'Chill', 'r&b' => 'Chill', 'soul' => 'Chill',

        // Happy
        'pop' => 'Happy', 'k-pop' => 'Happy', 'c-pop' => 'Happy', 'mandopop' => 'Happy',
        'disco' => 'Happy', 'funk' => 'Happy',

        // Hype
        'edm' => 'Hype', 'electro' => 'Hype', 'electroclash' => 'Hype',
        'house' => 'Hype', 'techno' => 'Hype', 'dubstep' => 'Hype',
        'trap' => 'Hype', 'hip hop' => 'Hype', 'phonk' => 'Hype', 'brazilian phonk' => 'Hype',
        'witch house' => 'Hype', 'rock' => 'Hype', 'metal' => 'Hype',
    ];

    foreach ($g as $genre) {
        foreach ($map as $needle => $mood) {
            if (str_contains($genre, $needle)) return $mood;
        }
    }
    return null;
}

protected static function activityFromGenres(array $genres): ?string
{
    $g = self::norm($genres);

    $map = [
        // study / sleep
        'lo-fi' => 'Study', 'lofi' => 'Study', 'ambient' => 'Sleep', 'chillhop' => 'Study',
        'classical' => 'Study', 'acoustic' => 'Study', 'jazz' => 'Study',
        'indie pop' => 'Study', 'bedroom pop' => 'Study',

        // workout / party
        'edm' => 'Workout', 'electro' => 'Workout', 'electroclash' => 'Workout',
        'techno' => 'Workout', 'dubstep' => 'Workout', 'trap' => 'Workout',
        'hip hop' => 'Workout', 'rock' => 'Workout', 'metal' => 'Workout',
        'phonk' => 'Workout', 'brazilian phonk' => 'Workout',

        // cheerful party pop
        'pop' => 'Party', 'k-pop' => 'Party', 'c-pop' => 'Party', 'mandopop' => 'Party',
        'disco' => 'Party', 'funk' => 'Party', 'house' => 'Party', 'witch house' => 'Party',
    ];

    foreach ($g as $genre) {
        foreach ($map as $needle => $activity) {
            if (str_contains($genre, $needle)) return $activity;
        }
    }
    return null;
}

    protected static function norm(array $genres): array
    {
        return array_map(fn ($g) => mb_strtolower(trim($g)), $genres);
    }


}
