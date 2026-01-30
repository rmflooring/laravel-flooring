@php
    $isDisabled = empty($c['href']);
@endphp

<a
  @if(!$isDisabled) href="{{ $c['href'] }}" @endif
  class="block border rounded-2xl p-5 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:ring-2 hover:ring-white/60 dark:hover:ring-gray-700 {{ $c['accent'] }} {{ $isDisabled ? 'cursor-default' : 'cursor-pointer' }}"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $c['title'] }}
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                {{ $c['subtitle'] }}
            </p>
        </div>

        <div class="h-10 w-10 rounded-xl bg-white/70 dark:bg-gray-900/30 flex items-center justify-center border border-white/60 dark:border-gray-700">
            <svg class="h-5 w-5 text-gray-700 dark:text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/>
            </svg>
        </div>
    </div>

    <div class="mt-4">
        @if($isDisabled)
            <span class="inline-flex items-center rounded-lg bg-white/70 px-3 py-1.5 text-xs font-medium text-gray-700 dark:bg-gray-900/30 dark:text-gray-200 border border-white/60 dark:border-gray-700">
                Coming soon
            </span>
        @else
    <span
      class="inline-flex items-center rounded-lg bg-white/80 px-3 py-1.5 text-xs font-medium text-gray-800 dark:bg-gray-900/30 dark:text-gray-100 border border-white/60 dark:border-gray-700"
    >
        Open
        <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h10m0 0v10m0-10L9 15"/>
        </svg>
    </span>
@endif
    </div>
</a>