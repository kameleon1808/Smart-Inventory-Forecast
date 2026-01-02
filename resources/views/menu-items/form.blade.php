<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Menu') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $menuItem->exists ? __('Edit menu item') : __('Add menu item') }}
                </h2>
            </div>
            <a href="{{ route('menu-items.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back to list') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ $menuItem->exists ? route('menu-items.update', $menuItem) : route('menu-items.store') }}" class="space-y-6">
                    @csrf
                    @if ($menuItem->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $menuItem->name) }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @checked(old('is_active', $menuItem->is_active ?? true))>
                        <x-input-label for="is_active" :value="__('Active')" class="mb-0" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
