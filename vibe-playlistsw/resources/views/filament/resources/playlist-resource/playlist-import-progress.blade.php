{{-- always return a single root element for Livewire --}}
<div>
  @php
    $show = ($status || $percent !== null);
  @endphp

  @if ($show)
    <div class="rounded-xl border border-white/10 bg-white/5 p-3">
      <div class="flex items-center justify-between gap-3 mb-2">
        <div class="text-sm font-semibold">
          Import status:
          <span @class([
            'px-2 py-0.5 rounded text-xs',
            'bg-yellow-500/20 text-yellow-200' => $status === 'running',
            'bg-gray-500/20 text-gray-200'     => $status === 'queued' || !$status,
            'bg-green-500/20 text-green-200'   => $status === 'done',
            'bg-red-500/20 text-red-200'       => $status === 'failed',
          ])>
            {{ $status ?? '—' }}
          </span>
        </div>
        <div class="text-xs opacity-75">
          {{ $total ? "{$done} / {$total}" : ($done ?: '0') }}
        </div>
      </div>

      @if ($percent !== null)
        <div class="w-full h-2 bg-white/10 rounded">
          <div class="h-2 bg-green-500 rounded" style="width: {{ $percent }}%"></div>
        </div>
        <div class="text-xs mt-1 opacity-75">{{ $percent }}%</div>
      @else
        <div class="text-xs opacity-75">Waiting for Spotify…</div>
      @endif

      <button
        type="button"
        wire:click="$refresh"
        class="mt-2 text-xs px-2 py-1 rounded bg-white/10 hover:bg-white/20">
        Refresh now
      </button>
    </div>
  @else
    {{-- Empty state (still inside the single root <div>) --}}
    <div class="rounded-xl border border-white/10 bg-white/5 p-3 text-xs opacity-70">
      No import activity yet.
    </div>
  @endif
</div>
