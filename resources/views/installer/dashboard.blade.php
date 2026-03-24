<x-mobile-layout title="My Jobs">

    @php
        $statusColors = [
            'created'         => 'bg-gray-100 text-gray-700',
            'scheduled'       => 'bg-blue-100 text-blue-800',
            'in_progress'     => 'bg-amber-100 text-amber-800',
            'partial'         => 'bg-purple-100 text-purple-800',
            'site_not_ready'  => 'bg-orange-100 text-orange-800',
            'needs_levelling' => 'bg-orange-100 text-orange-800',
            'needs_attention' => 'bg-red-100 text-red-800',
            'completed'       => 'bg-green-100 text-green-800',
            'cancelled'       => 'bg-red-100 text-red-800',
        ];
    @endphp

    {{-- Flash --}}
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 px-4 py-3 flex items-center justify-between gap-3">
            <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
            <button onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400 text-lg leading-none">&times;</button>
        </div>
    @endif

    {{-- No installer linked --}}
    @if(! $installer)
        <div class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 p-6 text-center">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Your account is not linked to an installer record yet.</p>
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Please contact your administrator.</p>
        </div>
        @php return; @endphp
    @endif

    {{-- Header --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Welcome</p>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $installer->company_name }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ today()->format('l, F j, Y') }}</p>
    </div>

    {{-- Today --}}
    @if($today->isNotEmpty())
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400 px-1 mb-2">Today</p>
            <div class="space-y-2">
                @foreach($today as $wo)
                    @include('installer._wo-card', ['wo' => $wo, 'statusColors' => $statusColors, 'highlight' => true])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Upcoming --}}
    @if($upcoming->isNotEmpty())
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 px-1 mb-2">Upcoming</p>
            <div class="space-y-2">
                @foreach($upcoming as $wo)
                    @include('installer._wo-card', ['wo' => $wo, 'statusColors' => $statusColors, 'highlight' => false])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Nothing scheduled --}}
    @if($today->isEmpty() && $upcoming->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming jobs scheduled.</p>
        </div>
    @endif

    {{-- Past --}}
    <div>
        <div class="flex items-center justify-between px-1 mb-2">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                Past Jobs {{ $showAll ? '' : '(Last 30 Days)' }}
            </p>
            <a href="{{ route('installer.dashboard', $showAll ? [] : ['show_all' => 1]) }}"
               class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                {{ $showAll ? 'Show Recent' : 'Show All' }}
            </a>
        </div>

        @if($past->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-4 py-3 text-center">
                <p class="text-sm text-gray-400 dark:text-gray-500">No past jobs found.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($past as $wo)
                    @include('installer._wo-card', ['wo' => $wo, 'statusColors' => $statusColors, 'highlight' => false])
                @endforeach
            </div>
        @endif
    </div>

</x-mobile-layout>
