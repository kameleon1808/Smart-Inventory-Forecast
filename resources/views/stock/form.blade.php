<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Inventory') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    @switch($type)
                        @case(\App\Domain\Inventory\StockTransaction::TYPE_RECEIPT)
                            {{ __('New receipt') }}
                            @break
                        @case(\App\Domain\Inventory\StockTransaction::TYPE_WASTE)
                            {{ __('Waste') }}
                            @break
                        @case(\App\Domain\Inventory\StockTransaction::TYPE_INTERNAL_USE)
                            {{ __('Internal use') }}
                            @break
                        @default
                            {{ __('Stock transaction') }}
                    @endswitch
                </h2>
            </div>
            <a href="{{ route('stock.ledger') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back to ledger') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('stock.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                            <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select warehouse') }}</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('warehouse_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="happened_at" :value="__('Date/time')" />
                            <x-text-input id="happened_at" name="happened_at" type="datetime-local" class="mt-1 block w-full" value="{{ old('happened_at', now()->format('Y-m-d\TH:i')) }}" required />
                            <x-input-error :messages="$errors->get('happened_at')" class="mt-2" />
                        </div>
                    </div>

                    @if ($type === \App\Domain\Inventory\StockTransaction::TYPE_RECEIPT)
                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <x-input-label for="supplier_name" :value="__('Supplier name')" />
                                <x-text-input id="supplier_name" name="supplier_name" type="text" class="mt-1 block w-full" value="{{ old('supplier_name') }}" />
                                <x-input-error :messages="$errors->get('supplier_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="reference" :value="__('Invoice / Reference')" />
                                <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" value="{{ old('reference') }}" />
                                <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                            </div>
                        </div>
                    @else
                        <div>
                            <x-input-label for="reason" :value="__('Reason')" />
                            <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" value="{{ old('reason') }}" />
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="item_id" :value="__('Item')" />
                            <select id="item_id" name="item_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select item') }}</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>{{ $item->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('item_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="unit_id" :value="__('Unit')" />
                            <select id="unit_id" name="unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select unit') }}</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        <div>
                            <x-input-label for="quantity" :value="__('Quantity')" />
                            <x-text-input id="quantity" name="quantity" type="number" step="0.0001" class="mt-1 block w-full" value="{{ old('quantity') }}" required />
                            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="unit_cost" :value="__('Unit cost (optional)')" />
                            <x-text-input id="unit_cost" name="unit_cost" type="number" step="0.0001" class="mt-1 block w-full" value="{{ old('unit_cost') }}" />
                            <x-input-error :messages="$errors->get('unit_cost')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Post transaction') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
