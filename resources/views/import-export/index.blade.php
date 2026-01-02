<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Data IO') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Import / Export') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'import-dispatched')
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Import dispatched to queue. Check job list below for status.') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Import CSV') }}</h3>
                <form method="POST" action="{{ route('import.run') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-3 items-end">
                    @csrf
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="items">{{ __('Items') }}</option>
                            <option value="unit_conversions">{{ __('Unit conversions') }}</option>
                            <option value="recipes">{{ __('Recipes') }}</option>
                            <option value="opening_stock">{{ __('Opening stock count') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="file" :value="__('CSV file')" />
                        <input id="file" name="file" type="file" accept=".csv,text/csv" class="mt-1 block w-full text-sm" required />
                    </div>
                    <div>
                        <x-input-label for="dry_run" :value="__('Dry run')" />
                        <label class="inline-flex items-center mt-2">
                            <input type="checkbox" id="dry_run" name="dry_run" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('Validate only') }}</span>
                        </label>
                    </div>
                    <div class="md:col-span-3">
                        <x-primary-button>{{ __('Upload') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Export CSV') }}</h3>
                <form method="POST" action="{{ route('export.run') }}" class="grid gap-4 md:grid-cols-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="ledger">{{ __('Stock ledger') }}</option>
                            <option value="balances">{{ __('Current balances') }}</option>
                            <option value="suggestions">{{ __('Procurement suggestions') }}</option>
                            <option value="variance">{{ __('Variance report') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="from" :value="__('From date (optional)')" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="to" :value="__('To date (optional)')" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" />
                    </div>
                    <div class="md:col-span-1">
                        <x-primary-button class="mt-6">{{ __('Download') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Recent imports') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Result') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($jobs as $job)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $job->type }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $job->status }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        @if ($job->result)
                                            {{ json_encode($job->result) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $job->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-sm text-gray-500">{{ __('No imports yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
