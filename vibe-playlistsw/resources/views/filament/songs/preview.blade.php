<div class="space-y-4">
  @if($song->spotify_id)
    <iframe style="border-radius:12px"
            src="https://open.spotify.com/embed/track/{{ $song->spotify_id }}"
            width="100%" height="152" frameborder="0"
            allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
            loading="lazy"></iframe>
  @endif

  @if($song->preview_url)
    <audio controls style="width:100%">
      <source src="{{ $song->preview_url }}" type="audio/mpeg">
    </audio>
  @endif
</div>
