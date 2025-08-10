<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  @forelse($tracks as $t)
    <div class="p-3 border rounded-xl flex gap-3 items-center">
      <img src="{{ $t['album']['images'][2]['url'] ?? $t['album']['images'][0]['url'] ?? '' }}" class="w-12 h-12 rounded" />
      <div class="flex-1">
        <div class="font-semibold">{{ $t['name'] }}</div>
        <div class="text-sm opacity-70">{{ collect($t['artists'])->pluck('name')->join(', ') }}</div>
      </div>
      <a target="_blank" class="text-blue-600 underline" href="{{ $t['external_urls']['spotify'] ?? '#' }}">Open</a>
    </div>
  @empty
    <p>No recs right now.</p>
  @endforelse
</div>
