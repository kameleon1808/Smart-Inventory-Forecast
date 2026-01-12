<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Inventory') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Stock ledger') }}
                </h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('stock.receipt.create') }}" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">{{ __('New receipt') }}</a>
                <a href="{{ route('stock.waste.create') }}" class="rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">{{ __('Waste') }}</a>
                <a href="{{ route('stock.internal.create') }}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">{{ __('Internal use') }}</a>
                <a href="{{ route('stock.adjustment.create') }}" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">{{ __('Adjustment') }}</a>
                {{-- <a href="{{ route('stock-counts.create') }}" class="rounded-md bg-slate-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">{{ __('Stock count') }}</a> --}}
                <a href="{{ route('stock-counts.index') }}" class="rounded-md bg-slate-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">{{ __('Stock count') }}</a>

            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-4 items-end">
                    <div>
                        <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                        <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(($filters['warehouse_id'] ?? null) == $warehouse->id)>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All') }}</option>
                            @foreach ([\App\Domain\Inventory\StockTransaction::TYPE_RECEIPT => 'Receipt', \App\Domain\Inventory\StockTransaction::TYPE_WASTE => 'Waste', \App\Domain\Inventory\StockTransaction::TYPE_INTERNAL_USE => 'Internal use', \App\Domain\Inventory\StockTransaction::TYPE_ADJUSTMENT => 'Adjustment', \App\Domain\Inventory\StockTransaction::TYPE_STOCK_COUNT_ADJUSTMENT => 'Stock count'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="from" :value="__('From date')" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" value="{{ $filters['from'] ?? '' }}" />
                    </div>
                    <div>
                        <x-input-label for="to" :value="__('To date')" />
                        <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" value="{{ $filters['to'] ?? '' }}" />
                    </div>
                    <div class="md:col-span-4">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        <a href="{{ route('stock.ledger') }}" class="ml-3 text-sm text-gray-600 hover:text-gray-900">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Warehouse') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Reference') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Lines') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($transactions as $tx)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $tx->happened_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $tx->warehouse->name }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', strtolower($tx->type)) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $tx->reference ?? $tx->supplier_name ?? $tx->reason ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <ul class="space-y-1">
                                            @foreach ($tx->lines as $line)
                                                <li>
                                                    {{ $line->item->name }}:
                                                    <span class="font-mono">{{ $line->quantity }} {{ $line->unit->symbol ?? $line->unit->name }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-sm text-gray-500">{{ __('No transactions found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
