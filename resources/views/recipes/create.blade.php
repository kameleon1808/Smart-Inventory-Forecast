<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Recipe') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $menuItem->name }}
                </h2>
            </div>
            <a href="{{ route('menu-items.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Recipe version created.') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 x-data="recipeBuilder({ ingredients: @js($initialIngredients), items: @js($itemsForJs) })"
                 x-init="init()">
                <form method="POST" action="{{ route('recipes.store', $menuItem) }}" class="space-y-6">
                    @csrf

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="valid_from" :value="__('Valid from')" />
                            <x-text-input id="valid_from" name="valid_from" type="date" class="mt-1 block w-full" value="{{ $defaultValidFrom }}" required />
                            @if ($latestVersion)
                                <p class="mt-1 text-xs text-gray-600">
                                    {{ __('Latest version starts on :date and has :count ingredients. Choose a new start date to avoid overlap.', ['date' => $latestVersion->valid_from->format('Y-m-d'), 'count' => $latestVersion->ingredients->count()]) }}
                                </p>
                            @endif
                            <x-input-error :messages="$errors->get('valid_from')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Ingredients') }}</h3>
                            <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800" @click="addLine()">{{ __('Add ingredient') }}</button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(line, index) in ingredients" :key="index">
                                <div class="grid gap-3 md:grid-cols-3 items-end">
                                    <div>
                                        <x-input-label :value="__('Item')" />
                                        <select :name="`ingredients[${index}][item_id]`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="line.item_id" @change="setUnit(index)">
                                            <option value="">{{ __('Select item') }}</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('ingredients.*.item_id')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label :value="__('Current unit')" />
                                        <x-text-input type="text" class="mt-1 block w-full bg-gray-50" x-bind:value="unitLabel(line)" readonly />
                                        <input type="hidden" :name="`ingredients[${index}][unit_id]`" x-model="line.unit_id">
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <div class="flex-1">
                                            <x-input-label :value="__('Quantity')" />
                                            <x-text-input type="number" step="0.0001" min="0.0001" class="mt-1 block w-full" x-bind:name="`ingredients[${index}][quantity]`" x-model="line.quantity" />
                                        </div>
                                        <button type="button" class="text-sm text-gray-500 hover:text-gray-900 pb-2" @click="removeLine(index)">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <x-input-error :messages="$errors->get('ingredients')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Publish version') }}</x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('recipeBuilder', ({ ingredients, items }) => ({
                ingredients: ingredients.length ? ingredients : [{ item_id: '', unit_id: '', quantity: '' }],
                items: items || [],
                init() {
                    this.ingredients = this.ingredients.map((line) => ({
                        item_id: line.item_id === null ? '' : String(line.item_id),
                        unit_id: line.unit_id === null ? '' : String(line.unit_id),
                        quantity: line.quantity,
                    }));
                    this.ingredients.forEach((line, idx) => this.ensureUnit(idx));
                },
                addLine() {
                    this.ingredients.push({ item_id: '', unit_id: '', quantity: '' });
                },
                removeLine(index) {
                    this.ingredients.splice(index, 1);
                    if (this.ingredients.length === 0) {
                        this.addLine();
                    }
                },
                setUnit(index) {
                    this.ensureUnit(index);
                },
                ensureUnit(index) {
                    const line = this.ingredients[index];
                    if (! line) return;
                    const item = this.items.find((i) => Number(i.id) === Number(line.item_id));
                    if (item && (! line.unit_id || line.unit_id !== String(item.base_unit_id))) {
                        line.unit_id = String(item.base_unit_id);
                        line.unit_label = item.base_unit_label;
                    }
                },
                unitLabel(line) {
                    if (line.unit_label) return line.unit_label;
                    const item = this.items.find((i) => String(i.base_unit_id) === String(line.unit_id));
                    return item?.base_unit_label ?? '';
                },
            }));
        });
    </script>
</x-app-layout>
