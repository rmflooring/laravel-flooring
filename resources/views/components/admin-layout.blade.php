<x-app-layout>
    {{-- Optional page header slot --}}
    @isset($header)
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endisset

    {{-- Page content --}}
    {{ $slot }}
</x-app-layout>
