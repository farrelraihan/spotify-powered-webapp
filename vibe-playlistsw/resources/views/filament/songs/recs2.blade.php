{{-- resources/views/filament/songs/recs2.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  @forelse($tracks as $t)
    @php
      $img = data_get($t, 'album.images.1.url')
          ?? data_get($t, 'album.images.0.url')
          ?? '';
      $title   = $t['name'] ?? '—';
      $artists = collect($t['artists'] ?? [])->pluck('name')->filter()->join(', ');
      $open    = data_get($t, 'external_urls.spotify', '#');
      $preview = $t['preview_url'] ?? null;
    @endphp

    <div class="p-3 border border-white/10 rounded-xl flex gap-3 items-start bg-white/5">
      @if($img)
        <img src="{{ $img }}" alt="" class="w-12 h-12 rounded object-cover flex-shrink-0" loading="lazy">
      @else
        <div class="w-12 h-12 rounded bg-white/10 flex items-center justify-center text-xs">No art</div>
      @endif

      <div class="flex-1 min-w-0">
        <div class="font-semibold truncate">{{ $title }}</div>
        <div class="text-sm opacity-70 truncate">{{ $artists ?: 'Unknown artist' }}</div>

        @if($preview)
          <div class="mt-2">
            <audio controls class="w-full h-8">
              <source src="{{ $preview }}" type="audio/mpeg">
            </audio>
          </div>
        @endif
      </div>

      <a target="_blank"
         class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-full bg-green-500 hover:bg-green-600 transition text-white"
         href="{{ $open }}"
         title="Open in Spotify">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.5 14.42c-.2.32-.62.42-.95.2-2.59-1.58-5.86-1.94-9.7-1.08-.38.08-.77-.16-.85-.54-.08-.38.16-.77.54-.85 4.2-.95 7.68-.55 10.55 1.22.33.2.43.63.21.95zm1.34-2.78c-.25.41-.79.54-1.2.29-2.96-1.82-7.48-2.35-10.99-1.3-.46.13-.92-.13-1.06-.59-.14-.46.12-.92.58-1.06 4.03-1.18 8.92-.61 12.37 1.48.41.25.54.79.29 1.18zm.12-2.9c-3.56-2.11-9.44-2.3-12.84-1.28-.55.16-1.14-.14-1.3-.7-.16-.55.14-1.14.69-1.3 3.89-1.18 10.41-.95 14.51 1.49.49.29.66.93.37 1.42-.29.49-.93.66-1.42.37z"/>
        </svg>
      </a>
    </div>
  @empty
    <p>No recs right now.</p>
  @endforelse
</div>
