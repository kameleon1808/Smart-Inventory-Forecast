<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Alert thresholds') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Threshold configuration') }}
                </h2>
            </div>
            <a href="{{ route('anomalies.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back to alerts') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'threshold-saved')
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Threshold saved.') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Add / Update Threshold') }}</h3>
                <form method="POST" action="{{ route('anomalies.thresholds.store') }}" class="grid gap-4 md:grid-cols-3">
                    @csrf
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @foreach (['waste_spike','variance_spike','adjustment_count'] as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="item_id" :value="__('Item (optional)')" />
                        <select id="item_id" name="item_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="category_id" :value="__('Category (optional)')" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="absolute_threshold" :value="__('Absolute threshold')" />
                        <x-text-input id="absolute_threshold" name="absolute_threshold" type="number" step="0.01" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="percent_threshold" :value="__('Percent threshold (%)')" />
                        <x-text-input id="percent_threshold" name="percent_threshold" type="number" step="0.1" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="count_threshold" :value="__('Count threshold (per week)')" />
                        <x-text-input id="count_threshold" name="count_threshold" type="number" step="1" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="severity" :value="__('Severity')" />
                        <select id="severity" name="severity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (['low','medium','high'] as $severity)
                                <option value="{{ $severity }}">{{ ucfirst($severity) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <x-primary-button>{{ __('Save threshold') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Existing thresholds') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Target') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Absolute') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Percent') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Count') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Severity') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($thresholds as $threshold)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $threshold->type }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        @if ($threshold->item)
                                            {{ $threshold->item->name }}
                                        @elseif ($threshold->category)
                                            {{ $threshold->category->name }}
                                        @else
                                            {{ __('All') }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $threshold->absolute_threshold }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $threshold->percent_threshold }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ $threshold->count_threshold }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($threshold->severity) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-sm text-gray-500">{{ __('No thresholds configured yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
