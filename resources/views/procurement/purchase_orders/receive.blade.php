<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Receive for PO') }} #{{ $purchaseOrder->id }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $purchaseOrder->supplier_name }} — {{ $purchaseOrder->warehouse->name }}
                </h2>
            </div>
            <a href="{{ route('procurement.purchase-orders.show', $purchaseOrder) }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Back to PO') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ __('Please fix the errors and try again.') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <form method="POST" action="{{ route('procurement.purchase-orders.receive', $purchaseOrder) }}">
                    @csrf
                    <div class="px-6 py-4 border-b border-gray-200 grid gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label for="received_at" :value="__('Received at')" />
                            <x-text-input id="received_at" name="received_at" type="datetime-local" class="mt-1 block w-full" value="{{ old('received_at', $default_received_at) }}" required />
                            <x-input-error :messages="$errors->get('received_at')" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="reference" :value="__('Reference / invoice')" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" value="{{ old('reference') }}" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Item') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Ordered') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Received so far') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Remaining') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Receive now') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($lines as $index => $line)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $line['item_name'] }}</td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                            {{ number_format($line['ordered_display'], 2) }} {{ $line['unit']->symbol ?? $line['unit']->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                            {{ number_format($line['received_display'], 2) }} {{ $line['unit']->symbol ?? $line['unit']->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-mono text-gray-900">
                                            {{ number_format($line['remaining_display'], 2) }} {{ $line['unit']->symbol ?? $line['unit']->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <input type="hidden" name="lines[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
                                            <x-text-input type="number" step="0.01" min="0" name="lines[{{ $index }}][qty]" value="{{ old('lines.'.$index.'.qty', $line['remaining_display']) }}" class="w-28" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                        <x-primary-button>{{ __('Post receipt') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
