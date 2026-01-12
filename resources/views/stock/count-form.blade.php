<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Inventory') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $count->exists ? __('Edit stock count') : __('New stock count') }}
                </h2>
            </div>
            <a href="{{ route('stock.ledger') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back to ledger') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Saved') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ __('Please fix the errors and try again.') }}
                </div>
            @endif

            @if (session('count_summary'))
                <div class="rounded-md bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <p class="font-semibold">{{ __('Stock count posted.') }}</p>
                    <p>{{ __('Adjustments made for :count items.', ['count' => count(session('count_summary'))]) }}</p>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 x-data="stockCountForm({ lines: {{ json_encode($initialLines ?? []) }} })">
                <form method="POST" action="{{ $count->exists ? route('stock-counts.update', $count) : route('stock-counts.store') }}" class="space-y-6">
                    @csrf
                    @if ($count->exists)
                        @method('PUT')
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                            <select id="warehouse_id" name="warehouse_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select warehouse') }}</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $count->warehouse_id) == $warehouse->id)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('warehouse_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="counted_at" :value="__('Count date/time')" />
                            <x-text-input id="counted_at" name="counted_at" type="datetime-local" class="mt-1 block w-full" value="{{ old('counted_at', $count->counted_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required />
                            <x-input-error :messages="$errors->get('counted_at')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Counted items') }}</h3>
                            <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800" @click="addLine()">{{ __('Add line') }}</button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(line, index) in lines" :key="index">
                                <div class="grid gap-3 md:grid-cols-2 items-end">
                                    <div>
                                        <x-input-label :value="__('Item')" />
                                        <select x-bind:name="`lines[${index}][item_id]`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="line.item_id" required>
                                            <option value="">{{ __('Select item') }}</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}">
                                                    {{ $item->name }} ({{ $item->baseUnit?->symbol ?? $item->baseUnit?->name }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('lines.*.item_id')" class="mt-1" />
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <div class="flex-1">
                                            <x-input-label :value="__('Counted quantity (base)')" />
                                            <x-text-input type="number" step="0.0001" min="0" class="mt-1 block w-full" x-bind:name="`lines[${index}][counted_quantity]`" x-model="line.counted_quantity" required />
                                        </div>
                                        <button type="button" class="text-sm text-gray-500 hover:text-gray-900 pb-2" @click="removeLine(index)">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-primary-button>{{ __('Save draft') }}</x-primary-button>
                    </div>
                </form>

                @if ($count->exists && $count->status === \App\Domain\Inventory\StockCount::STATUS_DRAFT)
                    <form method="POST" action="{{ route('stock-counts.post', $count) }}" class="mt-4 flex justify-end">
                        @csrf
                        <button class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2" type="submit">{{ __('Post') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('stockCountForm', ({ lines }) => ({
                lines: lines.length ? lines : [{ item_id: '', counted_quantity: '' }],
                addLine() {
                    this.lines.push({ item_id: '', counted_quantity: '' });
                },
                removeLine(index) {
                    this.lines.splice(index, 1);
                    if (this.lines.length === 0) {
                        this.addLine();
                    }
                },
            }));
        });
    </script>
</x-app-layout>
