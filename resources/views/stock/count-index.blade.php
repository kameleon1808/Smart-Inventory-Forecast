<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Inventory') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ __('Stock counts') }}
                </h2>
            </div>
            <a href="{{ route('stock-counts.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('New stock count') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'count-saved')
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Stock count saved as draft.') }}
                    @if (session('created_count_id'))
                        <a href="{{ route('stock-counts.edit', session('created_count_id')) }}" class="font-semibold underline hover:text-emerald-900">{{ __('Continue editing') }}</a>
                    @endif
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <p class="text-sm text-gray-600">{{ __('Drafts stay here until you post them. Click Edit to continue a draft or Post from the edit screen.') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Warehouse') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Lines') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($counts as $count)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ optional($count->counted_at)->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $count->warehouse?->name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($count->status === \App\Domain\Inventory\StockCount::STATUS_DRAFT)
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ __('Draft') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Posted') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $count->lines_count }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($count->status === \App\Domain\Inventory\StockCount::STATUS_DRAFT)
                                            <a href="{{ route('stock-counts.edit', $count) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">{{ __('Edit') }}</a>
                                        @else
                                            <span class="text-gray-400">{{ __('Posted') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-sm text-gray-500">{{ __('No stock counts yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $counts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
