<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Reports') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Variance') }}
                </h2>
            </div>
            <a href="{{ route('menu-usage.create') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Enter usage') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-4 items-end">
                    <div>
                        <x-input-label for="from" :value="__('From date')" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" value="{{ $from }}" />
                    </div>
                    <div>
                        <x-input-label for="to" :value="__('To date')" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" value="{{ $to }}" />
                    </div>
                    <div class="md:col-span-2">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        <a href="{{ route('reports.index') }}" class="ml-3 text-sm text-gray-600 hover:text-gray-900">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Item') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Expected') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actual') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Variance') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Variance %') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Net change') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $items[$row['item_id']] ?? ('Item #'.$row['item_id']) }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row['expected'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row['actual'], 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-mono {{ $row['variance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($row['variance'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                        @if (!is_null($row['variance_percent']))
                                            {{ number_format($row['variance_percent'], 1) }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row['net_change'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-sm text-gray-500">{{ __('No data for selected range.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
