<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Forecasting') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Demand forecast') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Method: baseline (avg with day-of-week adjustment). Last trained:') }}
                    {{ $lastTrainedAt ? $lastTrainedAt->format('Y-m-d H:i') : __('n/a') }}
                </p>
            </div>
            <a href="{{ route('reports.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Variance report') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'forecast-dispatched')
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Forecast generation dispatched to the queue.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ __('Please fix the errors and try again.') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-4 items-end">
                    <div>
                        <x-input-label for="location_id" :value="__('Location')" />
                        <select id="location_id" name="location_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" @selected($loc->id === $location->id)>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="item_id" :value="__('Item (optional)')" />
                        <select id="item_id" name="item_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All items') }}</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" @selected((int) ($filters['item_id'] ?? 0) === $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="horizon" :value="__('Horizon (days)')" />
                        <x-text-input id="horizon" name="horizon" type="number" min="1" max="90" class="mt-1 block w-full" value="{{ $filters['horizon'] ?? 14 }}" />
                    </div>
                    <div class="md:col-span-1 flex gap-3">
                        <x-primary-button class="self-end">{{ __('Apply') }}</x-primary-button>
                        <a href="{{ route('forecast.index') }}" class="self-end text-sm text-gray-600 hover:text-gray-900">{{ __('Reset') }}</a>
                    </div>
                </form>

                <form method="POST" action="{{ route('forecast.run') }}" class="mt-6 flex items-center gap-4">
                    @csrf
                    <input type="hidden" name="location_id" value="{{ $location->id }}">
                    <input type="hidden" name="item_id" value="{{ $filters['item_id'] }}">
                    <input type="hidden" name="horizon" value="{{ $filters['horizon'] }}">
                    <x-primary-button>{{ __('Generate forecast') }}</x-primary-button>
                    <p class="text-sm text-gray-500">{{ __('Runs async via queue; latest results shown below.') }}</p>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Latest predictions') }}</h3>
                    <span class="text-sm text-gray-500">{{ $results->count() }} {{ __('rows') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Item') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Prediction') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('CI lower') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('CI upper') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($results as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row->date->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row->item->name ?? $row->item_id }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row->predicted_qty_in_base, 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row->lower, 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row->upper, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-sm text-gray-500">
                                        {{ __('No forecasts yet for this location.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
