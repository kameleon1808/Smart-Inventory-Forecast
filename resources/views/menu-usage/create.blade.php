<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Menu usage') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Daily usage entry') }}
                </h2>
            </div>
            <a href="{{ route('reports.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Expected consumption report') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Usage saved and consumption recalculated.') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('menu-usage.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid gap-6 md:grid-cols-3 items-end">
                        <div>
                            <x-input-label for="used_on" :value="__('Date')" />
                            <x-text-input id="used_on" name="used_on" type="date" class="mt-1 block w-full" value="{{ old('used_on', $date) }}" required />
                            <x-input-error :messages="$errors->get('used_on')" class="mt-2" />
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($menuItems as $item)
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <span class="col-span-2 text-sm text-gray-800">{{ $item->name }}</span>
                                <x-text-input type="number" step="0.01" min="0" name="usages[{{ $item->id }}]" class="mt-1 block w-full" value="{{ old('usages.'.$item->id) }}" placeholder="{{ __('Qty sold/used') }}" />
                            </div>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('usages')" class="mt-2" />

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Save usage') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
