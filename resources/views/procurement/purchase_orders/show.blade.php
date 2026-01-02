<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Purchase Order') }} #{{ $purchaseOrder->id }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $purchaseOrder->supplier_name }} — {{ $purchaseOrder->warehouse->name }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">{{ ucfirst(strtolower(str_replace('_', ' ', $purchaseOrder->status))) }}</p>
            </div>
            <div class="flex items-center gap-3">
                @can('approve-po')
                    @if ($purchaseOrder->status === \App\Domain\Procurement\PurchaseOrder::STATUS_DRAFT)
                        <form method="POST" action="{{ route('procurement.purchase-orders.approve', $purchaseOrder) }}">
                            @csrf
                            <x-primary-button>{{ __('Approve') }}</x-primary-button>
                        </form>
                    @endif
                @endcan
                @can('approve-po')
                    @if (! in_array($purchaseOrder->status, [\App\Domain\Procurement\PurchaseOrder::STATUS_CLOSED, \App\Domain\Procurement\PurchaseOrder::STATUS_CANCELLED]))
                        <a href="{{ route('procurement.purchase-orders.receive-form', $purchaseOrder) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('Receive goods') }}
                        </a>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'receipt-posted')
                <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    {{ __('Receipt posted successfully.') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Order lines') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Item') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Ordered') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Received') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Remaining') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($lines as $line)
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Receipts') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Received at') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Lines') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($receipts as $receipt)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $receipt->received_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <ul class="space-y-1">
                                            @foreach ($receipt->lines as $line)
                                                <li class="font-mono text-sm text-gray-900">
                                                    {{ number_format($line->qty_display, 2) }}
                                                    {{ $line->unitDisplay->symbol ?? $line->unitDisplay->name }}
                                                    — {{ $line->item->name }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm text-gray-500">
                                        {{ __('No receipts yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
