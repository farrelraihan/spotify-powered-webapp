<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SongIngestController extends Controller
{
    public function store(Request $request, SongIngestService $ingest)
    {
        $data = $request->validate(['url' => 'required|string']);
        $song = $ingest->ingest($data['url'], optional($request->user())->id);

        return back()->with('status', 'Song added: '.$song->name);
    }
}
