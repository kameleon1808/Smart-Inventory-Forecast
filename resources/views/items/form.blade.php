<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Inventory') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $item->exists ? __('Edit item') : __('Add item') }}
                </h2>
            </div>
            <a href="{{ route('items.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Back to list') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ __('Saved') }}
                    </div>
                @endif

                <form method="POST" action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}" class="space-y-6">
                    @csrf
                    @if ($item->exists)
                        @method('PUT')
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="sku" :value="__('SKU')" />
                            <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" value="{{ old('sku', $item->sku) }}" required />
                            <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $item->name) }}" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="category_id" :value="__('Category')" />
                            <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="base_unit_id" :value="__('Base unit')" />
                            <select id="base_unit_id" name="base_unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">{{ __('Select unit') }}</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('base_unit_id', $item->base_unit_id) == $unit->id)>
                                        {{ $unit->name }} ({{ $unit->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('base_unit_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="pack_size" :value="__('Pack size')" />
                            <x-text-input id="pack_size" name="pack_size" type="number" step="0.001" min="0" class="mt-1 block w-full" value="{{ old('pack_size', $item->pack_size ?? 1) }}" required />
                            <x-input-error :messages="$errors->get('pack_size')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="lead_time_days" :value="__('Lead time (days)')" />
                            <x-text-input id="lead_time_days" name="lead_time_days" type="number" min="0" step="1" class="mt-1 block w-full" value="{{ old('lead_time_days', $item->lead_time_days ?? 0) }}" required />
                            <x-input-error :messages="$errors->get('lead_time_days')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="min_stock" :value="__('Minimum stock')" />
                            <x-text-input id="min_stock" name="min_stock" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('min_stock', $item->min_stock ?? 0) }}" required />
                            <x-input-error :messages="$errors->get('min_stock')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="safety_stock" :value="__('Safety stock')" />
                            <x-text-input id="safety_stock" name="safety_stock" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('safety_stock', $item->safety_stock ?? 0) }}" required />
                            <x-input-error :messages="$errors->get('safety_stock')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="shelf_life_days" :value="__('Shelf life (days)')" />
                            <x-text-input id="shelf_life_days" name="shelf_life_days" type="number" min="1" step="1" class="mt-1 block w-full" value="{{ old('shelf_life_days', $item->shelf_life_days) }}" />
                            <x-input-error :messages="$errors->get('shelf_life_days')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-2 pt-6">
                            <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @checked(old('is_active', $item->is_active ?? true))>
                            <x-input-label for="is_active" :value="__('Active')" class="mb-0" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>
                            {{ __('Save item') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
