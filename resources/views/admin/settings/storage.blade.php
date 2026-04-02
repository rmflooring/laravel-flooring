{{-- resources/views/admin/settings/storage.blade.php --}}
<x-app-layout>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Storage Settings</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure where uploaded documents and photos are stored.</p>
    </div>

    {{-- NAS health status --}}
    @php
        $nasStatus      = \App\Models\Setting::get('nas_status', 'unknown');
        $nasLastChecked = \App\Models\Setting::get('nas_last_checked');
    @endphp
    <div class="mb-6 flex items-center gap-3 rounded-lg border px-4 py-3
        {{ $nasStatus === 'online'  ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : '' }}
        {{ $nasStatus === 'offline' ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : '' }}
        {{ $nasStatus === 'unknown' ? 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800' : '' }}">
        @if($nasStatus === 'online')
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-green-800 dark:text-green-200">NAS Storage is online</p>
                @if($nasLastChecked)<p class="text-xs text-green-700 dark:text-green-300">Last checked: {{ $nasLastChecked }}</p>@endif
            </div>
        @elseif($nasStatus === 'offline')
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-200">NAS Storage is OFFLINE</p>
                @if($nasLastChecked)<p class="text-xs text-red-700 dark:text-red-300">Last checked: {{ $nasLastChecked }}</p>@endif
            </div>
        @else
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
            </svg>
            <p class="text-sm text-gray-600 dark:text-gray-400">NAS status unknown — health check has not run yet.</p>
        @endif
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 dark:border-green-800 dark:bg-green-900/20">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</span>
        </div>
    @endif

    <div x-data="{ driver: '{{ $driver }}' }">

        {{-- Driver selection --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Storage Driver</h2>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                {{-- Local --}}
                <label class="relative cursor-pointer">
                    <input type="radio" name="_driver_select" value="local"
                           x-model="driver" class="peer sr-only">
                    <div class="flex flex-col gap-1.5 rounded-lg border-2 p-4 transition-colors
                                border-gray-200 dark:border-gray-600
                                peer-checked:border-blue-500 peer-checked:bg-blue-50
                                dark:peer-checked:bg-blue-900/20 dark:peer-checked:border-blue-500
                                hover:border-gray-300 dark:hover:border-gray-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Local Server</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Store files on this server's disk. Simple, no extra setup.</p>
                    </div>
                </label>

                {{-- S3 --}}
                <label class="relative cursor-pointer">
                    <input type="radio" name="_driver_select" value="s3"
                           x-model="driver" class="peer sr-only">
                    <div class="flex flex-col gap-1.5 rounded-lg border-2 p-4 transition-colors
                                border-gray-200 dark:border-gray-600
                                peer-checked:border-blue-500 peer-checked:bg-blue-50
                                dark:peer-checked:bg-blue-900/20 dark:peer-checked:border-blue-500
                                hover:border-gray-300 dark:hover:border-gray-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">S3 / Cloud</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">AWS S3, Wasabi, MinIO, DigitalOcean Spaces, or any S3-compatible service.</p>
                    </div>
                </label>

                {{-- SFTP --}}
                <label class="relative cursor-pointer">
                    <input type="radio" name="_driver_select" value="sftp"
                           x-model="driver" class="peer sr-only">
                    <div class="flex flex-col gap-1.5 rounded-lg border-2 p-4 transition-colors
                                border-gray-200 dark:border-gray-600
                                peer-checked:border-blue-500 peer-checked:bg-blue-50
                                dark:peer-checked:bg-blue-900/20 dark:peer-checked:border-blue-500
                                hover:border-gray-300 dark:hover:border-gray-500">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">SFTP / NAS</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Store on a NAS or remote server via SFTP.</p>
                    </div>
                </label>

            </div>
        </div>

        {{-- Settings form --}}
        <form method="POST" action="{{ route('admin.settings.storage.update') }}">
            @csrf
            @method('PUT')

            {{-- Hidden driver field kept in sync with radio selection --}}
            <input type="hidden" name="storage_driver" :value="driver">

            {{-- Local — no config needed --}}
            <div x-show="driver === 'local'" x-cloak>
                @if($localSymlink)
                <div class="mb-3 flex items-start gap-3 rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 dark:border-purple-800 dark:bg-purple-900/20">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-purple-800 dark:text-purple-200">Files are being stored on a NAS / external mount</p>
                        <p class="mt-0.5 text-xs text-purple-700 dark:text-purple-300">
                            <code class="rounded bg-purple-100 px-1 dark:bg-purple-900/40">storage/app/public</code> is a symlink pointing to
                            <code class="rounded bg-purple-100 px-1 dark:bg-purple-900/40">{{ $localSymlink }}</code>
                        </p>
                    </div>
                </div>
                @endif
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Files will be stored in <code class="rounded bg-blue-100 px-1 dark:bg-blue-900/40">storage/app/public/opportunities/</code> on this server and served via the <code class="rounded bg-blue-100 px-1 dark:bg-blue-900/40">/storage</code> symlink. No additional configuration required.
                    </p>
                </div>
            </div>

            {{-- S3 fields --}}
            <div x-show="driver === 's3'" x-cloak
                 class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-5">

                <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <p class="text-xs text-amber-800 dark:text-amber-200">
                        Requires the <code class="rounded bg-amber-100 px-1 dark:bg-amber-900/40">league/flysystem-aws-s3-v3</code> package.
                        Run: <code class="rounded bg-amber-100 px-1 dark:bg-amber-900/40">composer require league/flysystem-aws-s3-v3</code>
                    </p>
                </div>

                <h2 class="text-base font-semibold text-gray-900 dark:text-white">S3 / Cloud Storage Credentials</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Access Key ID <span class="text-red-500">*</span></label>
                        <input type="text" name="storage_s3_key" value="{{ old('storage_s3_key', $s3Key) }}"
                               autocomplete="off"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('storage_s3_key') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Secret Access Key <span class="text-red-500">*</span>
                            @if($s3SecretSet) <span class="text-xs font-normal text-gray-400">(saved — leave blank to keep)</span> @endif
                        </label>
                        <input type="password" name="storage_s3_secret"
                               placeholder="{{ $s3SecretSet ? '••••••••' : 'Enter secret key' }}"
                               autocomplete="new-password"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Region <span class="text-red-500">*</span></label>
                        <input type="text" name="storage_s3_region" value="{{ old('storage_s3_region', $s3Region) }}"
                               placeholder="us-east-1"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('storage_s3_region') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bucket Name <span class="text-red-500">*</span></label>
                        <input type="text" name="storage_s3_bucket" value="{{ old('storage_s3_bucket', $s3Bucket) }}"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('storage_s3_bucket') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-3">S3-Compatible / Advanced (optional)</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom Endpoint URL</label>
                            <input type="url" name="storage_s3_endpoint" value="{{ old('storage_s3_endpoint', $s3Endpoint) }}"
                                   placeholder="https://s3.wasabisys.com"
                                   class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="mt-1 text-xs text-gray-400">For Wasabi, MinIO, Spaces, etc. Leave blank for AWS.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Public CDN / Base URL</label>
                            <input type="url" name="storage_s3_url" value="{{ old('storage_s3_url', $s3Url) }}"
                                   placeholder="https://cdn.example.com"
                                   class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="mt-1 text-xs text-gray-400">Override the public URL used to serve files (e.g. a CDN).</p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="storage_s3_path_style" value="1"
                                   {{ $s3PathStyle ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Use path-style endpoint</span>
                        </label>
                        <p class="ml-6 mt-0.5 text-xs text-gray-400">Required for some S3-compatible services (e.g. MinIO).</p>
                    </div>
                </div>
            </div>

            {{-- SFTP fields --}}
            <div x-show="driver === 'sftp'" x-cloak
                 class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-5">

                <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <p class="text-xs text-amber-800 dark:text-amber-200">
                        Requires the <code class="rounded bg-amber-100 px-1 dark:bg-amber-900/40">league/flysystem-sftp-v3</code> package.
                        Run: <code class="rounded bg-amber-100 px-1 dark:bg-amber-900/40">composer require league/flysystem-sftp-v3</code>
                    </p>
                </div>

                <h2 class="text-base font-semibold text-gray-900 dark:text-white">SFTP / NAS Connection</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Host <span class="text-red-500">*</span></label>
                        <input type="text" name="storage_sftp_host" value="{{ old('storage_sftp_host', $sftpHost) }}"
                               placeholder="192.168.1.50 or nas.example.com"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('storage_sftp_host') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port <span class="text-red-500">*</span></label>
                        <input type="number" name="storage_sftp_port" value="{{ old('storage_sftp_port', $sftpPort) }}"
                               min="1" max="65535"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('storage_sftp_port') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="storage_sftp_username" value="{{ old('storage_sftp_username', $sftpUsername) }}"
                               autocomplete="off"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('storage_sftp_username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Password
                            @if($sftpPasswordSet) <span class="text-xs font-normal text-gray-400">(saved — leave blank to keep)</span> @endif
                        </label>
                        <input type="password" name="storage_sftp_password"
                               placeholder="{{ $sftpPasswordSet ? '••••••••' : 'Enter password' }}"
                               autocomplete="new-password"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Root Path <span class="text-red-500">*</span></label>
                        <input type="text" name="storage_sftp_root" value="{{ old('storage_sftp_root', $sftpRoot) }}"
                               placeholder="/volume1/FloorManager"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-400">Absolute path on the NAS where files will be stored.</p>
                        @error('storage_sftp_root') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Public Base URL</label>
                        <input type="url" name="storage_sftp_url" value="{{ old('storage_sftp_url', $sftpUrl) }}"
                               placeholder="https://nas.example.com/files"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-400">URL used to serve stored files publicly (requires a web server on the NAS).</p>
                    </div>
                </div>
            </div>

            {{-- Save button --}}
            <div class="mt-4">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Settings
                </button>
            </div>
        </form>

        {{-- Test Connection form — must be outside the save form (nested forms are invalid HTML) --}}
        <div x-show="driver !== 'local'" x-cloak class="mt-2">
            <form method="POST" action="{{ route('admin.settings.storage.test') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .23 2.71-1.158 2.71H4.956c-1.389 0-2.158-1.71-1.158-2.71L5 14.5"/>
                    </svg>
                    Test Connection
                </button>
            </form>
        </div>

        {{-- Info: existing files --}}
        <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <strong class="font-medium text-gray-700 dark:text-gray-300">Note:</strong>
                Existing uploaded files are not moved when you change the storage driver — they stay on their original disk.
                Only new uploads will go to the newly configured location.
            </p>
        </div>

    </div>
</div>
</x-app-layout>
