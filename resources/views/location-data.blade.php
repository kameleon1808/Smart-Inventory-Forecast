<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">
            {{ __('Location Data') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-500">{{ __('Active location') }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">
                        {{ $location?->name }} ({{ $location?->organization?->name }})
                    </h3>
                    <p class="mt-4 text-sm text-gray-600">
                        {{ __('This view represents data that requires manager or admin permissions.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
