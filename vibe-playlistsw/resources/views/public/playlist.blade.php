<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $playlist->name }}</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex flex-col items-center text-gray-100">
  <div class="w-full max-w-2xl mt-10 mb-8 px-6">
    <div class="bg-gray-800/90 rounded-2xl shadow-2xl p-8 flex flex-col items-center relative overflow-hidden border border-gray-700">
      <div class="absolute -top-8 -right-8 opacity-10 text-[8rem] select-none pointer-events-none">🎵</div>
      <h1 class="text-3xl md:text-4xl font-extrabold mb-2 text-white tracking-tight text-center">{{ $playlist->name }}</h1>
      @if($playlist->description)
        <p class="mb-4 text-lg text-gray-300 text-center">{{ $playlist->description }}</p>
      @endif
      <div class="flex items-center gap-4 mb-6">
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-900/70 text-blue-200 text-xs font-semibold">{{ $playlist->songs->count() }} songs</span>
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-700 text-gray-300 text-xs font-semibold">by {{ $playlist->user->name ?? 'Unknown' }}</span>
      </div>
      <div class="w-full">
        @if($playlist->songs->count())
          <ul class="divide-y divide-gray-700">
            @foreach($playlist->songs as $song)
              <li class="group flex items-center gap-4 py-4 px-2 rounded-lg hover:bg-gray-700/70 transition">
                <img class="w-14 h-14 rounded-lg shadow-sm object-cover border border-gray-700" src="{{ $song->cover_url }}" alt="">
                <div class="flex-1 min-w-0">
                  <div class="font-semibold text-white truncate">{{ $song->name }}</div>
                  <div class="text-sm text-gray-400 truncate">{{ $song->artists_string }}</div>
                </div>
                <a class="ml-2 px-3 py-1 rounded-full bg-blue-700 text-white text-xs font-medium shadow hover:bg-blue-800 transition group-hover:scale-105" target="_blank" href="{{ $song->spotify_url }}">Play</a>
              </li>
            @endforeach
          </ul>
        @else
          <div class="text-center text-gray-500 py-12 text-lg">This playlist is empty.</div>
        @endif
      </div>
    </div>
    <div class="mt-8 text-center">
      <a href="/playlists" class="inline-block px-5 py-2 rounded-lg bg-gray-700 text-white font-semibold shadow hover:bg-gray-600 transition">← Back to all playlists</a>
    </div>
  </div>
</body>
</html>
