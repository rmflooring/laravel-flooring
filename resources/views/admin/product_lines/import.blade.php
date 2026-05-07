<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Import Product Lines &amp; Styles
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Import errors --}}
            @if(session('import_errors'))
                <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                    <p class="font-semibold mb-2">Fix the following errors and re-upload the file:</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach(session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- File validation errors --}}
            @if($errors->any())
                <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Instructions card --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">How to use</h3>
                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li>Download the CSV template below and open it in Excel or Google Sheets.</li>
                    <li>Each <strong>LINE</strong> row defines a new product line. Each <strong>STYLE</strong> row beneath it belongs to that line.</li>
                    <li>You can have multiple LINE blocks in one file — just start a new LINE row to begin a new product line.</li>
                    <li>If a product line with the same name and vendor already exists, the entire import will be rejected.</li>
                    <li>Save as <strong>CSV (comma-separated)</strong> and upload below.</li>
                </ol>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-xs text-left text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 font-semibold">Column</th>
                                <th class="px-3 py-2 font-semibold">Used by</th>
                                <th class="px-3 py-2 font-semibold">Required?</th>
                                <th class="px-3 py-2 font-semibold">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr class="bg-blue-50 dark:bg-blue-900/20">
                                <td class="px-3 py-2 font-mono">row_type</td>
                                <td class="px-3 py-2">LINE &amp; STYLE</td>
                                <td class="px-3 py-2 text-red-600 dark:text-red-400">Required</td>
                                <td class="px-3 py-2">Must be <code>LINE</code> or <code>STYLE</code></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">line_name</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-red-600 dark:text-red-400">Required</td>
                                <td class="px-3 py-2">Name of the product line</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">product_type</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-red-600 dark:text-red-400">Required</td>
                                <td class="px-3 py-2">Must match an existing product type name exactly</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">vendor</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Must match an existing vendor company name exactly</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">manufacturer</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">model</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">collection</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">default_cost_price</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Numeric, e.g. <code>3.50</code></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">default_sell_price</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Numeric, e.g. <code>7.99</code></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">unit</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Unit code, e.g. <code>SF</code>, <code>SY</code>, <code>EA</code></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">width</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Width in inches</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">length</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Length in inches</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">line_status</td>
                                <td class="px-3 py-2">LINE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"><code>active</code>, <code>inactive</code>, or <code>dropped</code>. Defaults to <code>active</code>.</td>
                            </tr>
                            <tr class="bg-green-50 dark:bg-green-900/20">
                                <td class="px-3 py-2 font-mono">style_name</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-red-600 dark:text-red-400">Required</td>
                                <td class="px-3 py-2">Name of the style</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">sku</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">style_number</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">color</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">pattern</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">description</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">cost_price</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Numeric</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">sell_price</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Numeric</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">thickness</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Numeric, in mm or inches depending on your convention</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">units_per</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2">Integer, e.g. units per box</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">use_box_qty</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"><code>1</code> or <code>0</code>. Defaults to <code>0</code>.</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono">style_status</td>
                                <td class="px-3 py-2">STYLE</td>
                                <td class="px-3 py-2 text-gray-400">Optional</td>
                                <td class="px-3 py-2"><code>active</code>, <code>inactive</code>, or <code>dropped</code>. Defaults to <code>active</code>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    <a href="{{ route('admin.product_lines.import.template') }}"
                       class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 dark:text-blue-400 hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download CSV Template
                    </a>
                </div>
            </div>

            {{-- Upload form --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Upload CSV File</h3>

                <form method="POST"
                      action="{{ route('admin.product_lines.import.store') }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="flex items-center justify-center w-full mb-6">
                        <label for="csv_file"
                               class="flex flex-col items-center justify-center w-full h-40 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6" id="drop-label">
                                <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">CSV files only, max 5 MB</p>
                            </div>
                            <p class="hidden text-sm font-medium text-gray-700 dark:text-gray-300 pb-4" id="file-name-display"></p>
                            <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" class="hidden" />
                        </label>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                            Import
                        </button>
                        <a href="{{ route('admin.product_lines.index') }}"
                           class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:underline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('csv_file').addEventListener('change', function () {
            const label = document.getElementById('drop-label');
            const display = document.getElementById('file-name-display');
            if (this.files.length) {
                label.classList.add('hidden');
                display.classList.remove('hidden');
                display.textContent = this.files[0].name;
            }
        });
    </script>
</x-app-layout>
