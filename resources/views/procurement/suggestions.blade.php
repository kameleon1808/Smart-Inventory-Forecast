<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Procurement') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Suggestions') }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('procurement.purchase-orders.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Purchase orders') }}</a>
                <a href="{{ route('reports.variance') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Variance report') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-4 items-end">
                    <div>
                        <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                        <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected($warehouse_id == $warehouse->id)>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <x-primary-button>{{ __('Refresh') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($suggestions->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No suggestions at this time.') }}</p>
                @else
                    <form method="POST" action="{{ route('procurement.suggestions.store') }}">
                        @csrf
                        <input type="hidden" name="warehouse_id" value="{{ $warehouse_id }}">
                        <div class="mb-4">
                            <x-input-label for="supplier_name" :value="__('Supplier name')" />
                            <x-text-input id="supplier_name" name="supplier_name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('supplier_name')" class="mt-2" />
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3"></th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Item') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Stock') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Avg/day') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Forecast') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Open PO') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Suggested') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($suggestions as $row)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <input type="checkbox" name="lines[{{ $row['item']->id }}][item_id]" value="{{ $row['item']->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" checked>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-800">{{ $row['item']->name }}</td>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row['current_stock'], 2) }}</td>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row['avg_daily'], 2) }}</td>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row['forecast'], 2) }}</td>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-900">{{ number_format($row['open_po'], 2) }}</td>
                                            <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                                <x-text-input type="number" step="0.01" min="0" name="lines[{{ $row['item']->id }}][qty]" value="{{ $row['suggested_qty'] }}" class="w-28" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-primary-button>{{ __('Create PO (draft)') }}</x-primary-button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
