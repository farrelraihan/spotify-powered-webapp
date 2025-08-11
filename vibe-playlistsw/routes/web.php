<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SongIngestController;
use App\Models\Playlist;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/songs/ingest', [SongIngestController::class, 'store'])->name('songs.ingest');
});

Route::get('/p/{playlist}', function (Playlist $playlist){
    abort_unless($playlist->is_public, 404);
    $playlist->load('songs');
    return view('public.playlist', compact('playlist'));
});

require __DIR__.'/auth.php';
