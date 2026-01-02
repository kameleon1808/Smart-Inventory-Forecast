<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Controls') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Period lock') }}
                </h2>
            </div>
            <a href="{{ route('stock.ledger') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back to ledger') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'lock-updated')
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Lock date updated.') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('period-lock.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="lock_before_date" :value="__('Lock all transactions before date')" />
                        <x-text-input id="lock_before_date" name="lock_before_date" type="date" class="mt-1 block w-full" value="{{ $location->lock_before_date }}" />
                        <p class="text-xs text-gray-500 mt-1">{{ __('Non-admins cannot edit transactions earlier than this date.') }}</p>
                    </div>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
