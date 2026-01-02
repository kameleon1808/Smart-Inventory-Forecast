<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Welcome back</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Dashboard') }}
                </h2>
            </div>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                {{ __('Smart Inventory + Forecast') }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">{{ __('Inventory sources') }}</p>
                    <div class="mt-3 flex items-end justify-between">
                        <p class="text-3xl font-semibold text-gray-900">0</p>
                        <span class="text-xs text-gray-500">{{ __('Connect a datasource to get started') }}</span>
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">{{ __('Active products') }}</p>
                    <div class="mt-3 flex items-end justify-between">
                        <p class="text-3xl font-semibold text-gray-900">—</p>
                        <span class="text-xs text-gray-500">{{ __('Product sync coming soon') }}</span>
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">{{ __('Forecasts queued') }}</p>
                    <div class="mt-3 flex items-end justify-between">
                        <p class="text-3xl font-semibold text-gray-900">0</p>
                        <span class="text-xs text-gray-500">{{ __('No jobs scheduled yet') }}</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Next steps') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('Use these quick actions to wire up data and iterate on the product once modules are in place.') }}
                    </p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ __('Configure inventory sources') }}</p>
                                <p class="text-sm text-gray-600">{{ __('Define where stock will be pulled from and how it should sync.') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="mt-1 h-2 w-2 rounded-full bg-blue-500"></span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ __('Set up forecasting jobs') }}</p>
                                <p class="text-sm text-gray-600">{{ __('Queue periodic runs to keep forecasts fresh.') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="mt-1 h-2 w-2 rounded-full bg-amber-500"></span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ __('Define roles and policies') }}</p>
                                <p class="text-sm text-gray-600">{{ __('Use the policies folder to keep access predictable as modules grow.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Activity') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('Recent changes will appear here once data is flowing.') }}</p>
                    <div class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">
                        {{ __('No activity yet. Add your first data source to start tracking inventory and forecasts.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
