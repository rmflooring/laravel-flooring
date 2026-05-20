@extends('layouts.app')

@section('title', 'Installer Calendar Colors')

@section('content')
<div class="p-4 mx-auto max-w-4xl">

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Installer Calendar Colors</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Assign a color to each installer. Work Order calendar events will be tagged with that color in Outlook.
            </p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
            <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Info banner --}}
    <div class="mb-6 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        <span>
            Colors are pushed to all connected Microsoft 365 accounts when you save. Staff will see the installer's color on their Outlook calendar automatically.
            Use <strong>Re-push to Calendars</strong> if a new staff member has recently connected their Microsoft account.
        </span>
    </div>

    @if ($installers->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            No installers found. <a href="{{ route('admin.installers.index') }}" class="text-blue-600 underline dark:text-blue-400">Add an installer</a> first.
        </div>
    @else

    <form method="POST" action="{{ route('admin.settings.installer-colors.update') }}">
        @csrf

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 w-48">Installer</th>
                        <th class="px-6 py-3">Calendar Color</th>
                        <th class="px-6 py-3 w-24 text-center">Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($installers as $installer)
                    <tr
                        x-data="{
                            selected: '{{ $installer->calendar_color ?? '' }}',
                            colorMap: @js($colorMap),
                            getHex(p)  { return this.colorMap[p]?.hex  ?? '#9E9E9E'; },
                            getLabel(p){ return this.colorMap[p]?.label ?? p; }
                        }"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                    >
                        {{-- Installer name --}}
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $installer->company_name }}
                        </td>

                        {{-- Color swatches --}}
                        <td class="px-6 py-4">
                            <input type="hidden" name="colors[{{ $installer->id }}]" :value="selected">
                            <div class="flex flex-wrap gap-2">
                                {{-- "None" option --}}
                                <button
                                    type="button"
                                    @click="selected = ''"
                                    :class="selected === '' ? 'ring-2 ring-offset-1 ring-gray-500' : 'opacity-60 hover:opacity-100'"
                                    class="h-7 w-7 rounded-full border-2 border-dashed border-gray-400 bg-white dark:bg-gray-700 transition"
                                    title="No color"
                                ></button>

                                @foreach ($colorMap as $preset => $info)
                                <button
                                    type="button"
                                    @click="selected = '{{ $preset }}'"
                                    :class="selected === '{{ $preset }}' ? 'ring-2 ring-offset-1 ring-gray-700 scale-110' : 'opacity-70 hover:opacity-100 hover:scale-110'"
                                    class="h-7 w-7 rounded-full transition"
                                    style="background-color: {{ $info['hex'] }};"
                                    title="{{ $info['label'] }}"
                                ></button>
                                @endforeach
                            </div>
                        </td>

                        {{-- Preview badge --}}
                        <td class="px-6 py-4 text-center">
                            <template x-if="selected !== ''">
                                <span
                                    x-text="getLabel(selected)"
                                    class="inline-block rounded-full px-3 py-1 text-xs font-semibold text-white"
                                    :style="'background-color: ' + getHex(selected)"
                                ></span>
                            </template>
                            <template x-if="selected === ''">
                                <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                            </template>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit"
                class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                Save &amp; Push to Calendars
            </button>

            <form method="POST" action="{{ route('admin.settings.installer-colors.push') }}" class="inline">
                @csrf
                <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    Re-push to Calendars
                </button>
            </form>

            <span class="text-sm text-gray-400 dark:text-gray-500">
                Pushes current colors to all connected Microsoft 365 accounts.
            </span>
        </div>
    </form>

    @endif

</div>

@endsection
