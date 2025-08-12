<?php

namespace App\Support;

trait ParsesSpotifyUrls
{
    public function parseTrackId(string $input): ?string
    {
        // spotify:track:ID
        if (preg_match('~spotify:track:([A-Za-z0-9]+)~', $input, $m)) return $m[1];

        // https://open.spotify.com/track/ID?...
        if (preg_match('~open\.spotify\.com/track/([A-Za-z0-9]+)~', $input, $m)) return $m[1];

        // raw ID (just in case)
        if (preg_match('~^[A-Za-z0-9]{10,}$~', $input)) return $input;

        return null;
    }

    public function parsePlaylistId(string $input): ?string
    {
        if (preg_match('~spotify:playlist:([A-Za-z0-9]+)~', $input, $m)) return $m[1];
        if (preg_match('~open\.spotify\.com/playlist/([A-Za-z0-9]+)~', $input, $m)) return $m[1];
        if (preg_match('~^[A-Za-z0-9]{10,}$~', $input)) return $input;
        return null;
    }
}
