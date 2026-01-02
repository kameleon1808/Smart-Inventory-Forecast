<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Procurement') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Purchase orders') }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('procurement.suggestions') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Suggestions') }}</a>
                <a href="{{ route('reports.variance') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Variance report') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Supplier') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Warehouse') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Created') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">PO-{{ $order->id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $order->supplier_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $order->warehouse->name }}</td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusColors = [
                                                \App\Domain\Procurement\PurchaseOrder::STATUS_CLOSED => 'bg-emerald-50 text-emerald-700',
                                                \App\Domain\Procurement\PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'bg-amber-50 text-amber-700',
                                                \App\Domain\Procurement\PurchaseOrder::STATUS_DRAFT => 'bg-gray-100 text-gray-700',
                                            ];
                                            $badge = $statusColors[$order->status] ?? 'bg-indigo-50 text-indigo-700';
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">
                                            {{ ucwords(str_replace('_', ' ', strtolower($order->status))) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('procurement.purchase-orders.show', $order) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-sm text-gray-500">
                                        {{ __('No purchase orders yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
