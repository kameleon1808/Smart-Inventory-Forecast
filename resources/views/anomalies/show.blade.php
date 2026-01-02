<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Alert detail') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ ucfirst(str_replace('_', ' ', strtolower($anomaly->type))) }} — {{ $anomaly->happened_on->format('Y-m-d') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Severity') }}: {{ ucfirst($anomaly->severity) }} • {{ __('Status') }}: {{ $anomaly->status }}
                </p>
            </div>
            <a href="{{ route('anomalies.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back to alerts') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'status-updated')
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Status updated.') }}
                </div>
            @endif
            @if (session('status') === 'comment-added')
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('Comment added.') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Item') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $anomaly->item->name ?? __('N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Metric vs Threshold') }}</dt>
                        <dd class="text-sm font-mono text-gray-900">{{ number_format($anomaly->metric_value, 2) }} / {{ number_format($anomaly->threshold_value, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Status') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $anomaly->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('Created') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $anomaly->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Comments') }}</h3>
                    @can('resolve-anomalies')
                        <form method="POST" action="{{ route('anomalies.status', $anomaly) }}" class="flex items-center gap-2">
                            @csrf
                            <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ([\App\Domain\Anomaly\Anomaly::STATUS_OPEN, \App\Domain\Anomaly\Anomaly::STATUS_INVESTIGATING, \App\Domain\Anomaly\Anomaly::STATUS_RESOLVED, \App\Domain\Anomaly\Anomaly::STATUS_FALSE_POSITIVE] as $status)
                                    <option value="{{ $status }}" @selected($anomaly->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <x-primary-button>{{ __('Update') }}</x-primary-button>
                        </form>
                    @endcan
                </div>

                <ul class="space-y-4">
                    @forelse ($anomaly->comments as $comment)
                        <li class="border border-gray-100 rounded-lg p-4">
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <span>{{ $comment->user->name }}</span>
                                <span>{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 text-gray-900 text-sm">{{ $comment->comment }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500">{{ __('No comments yet.') }}</li>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('anomalies.comment', $anomaly) }}" class="mt-6 space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="comment" :value="__('Add comment')" />
                        <textarea id="comment" name="comment" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comment') }}</textarea>
                        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                    </div>
                    <x-primary-button>{{ __('Submit') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
