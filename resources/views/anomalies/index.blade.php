<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Alert center') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Anomalies') }}
                </h2>
            </div>
            <a href="{{ route('anomalies.thresholds') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Thresholds') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-4 items-end">
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['OPEN','INVESTIGATING','RESOLVED','FALSE_POSITIVE'] as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="severity" :value="__('Severity')" />
                        <select id="severity" name="severity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['low','medium','high'] as $severity)
                                <option value="{{ $severity }}" @selected($filters['severity'] === $severity)>{{ ucfirst($severity) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['waste_spike','variance_spike','adjustment_count'] as $type)
                                <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <x-primary-button class="mt-6">{{ __('Filter') }}</x-primary-button>
                        <a href="{{ route('anomalies.index') }}" class="ml-3 text-sm text-gray-600 hover:text-gray-900">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Item') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Metric') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($anomalies as $anomaly)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $anomaly->happened_on->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $anomaly->type) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $anomaly->item->name ?? __('N/A') }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                        {{ number_format($anomaly->metric_value, 2) }} / {{ number_format($anomaly->threshold_value, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $anomaly->status === 'OPEN' ? 'bg-amber-50 text-amber-700' : ($anomaly->status === 'RESOLVED' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700') }}">
                                            {{ $anomaly->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('anomalies.show', $anomaly) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-sm text-gray-500">
                                        {{ __('No anomalies found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $anomalies->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
