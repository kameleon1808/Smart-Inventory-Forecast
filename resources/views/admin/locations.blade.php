<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ __('Organization') }}</p>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                    {{ $organization->name ?? __('Organization settings') }}
                </h2>
            </div>
            @if (session('status'))
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    {{ __('Changes saved') }}
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Locations') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('Add a new location within the organization.') }}
                    </p>
                    <form method="POST" action="{{ route('admin.locations.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                        <div>
                            <x-input-label for="name" :value="__('Location name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <x-primary-button>{{ __('Add location') }}</x-primary-button>
                    </form>

                    <div class="mt-6 divide-y divide-gray-200">
                        @forelse ($locations as $location)
                            <div class="py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-base font-semibold text-gray-900">{{ $location->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $location->organization->name }}</p>
                                    </div>
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                        {{ __('Users: :count', ['count' => $location->users->count()]) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-gray-500">{{ __('No locations yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Assign users to locations') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('Select a user, choose a location, and set their role.') }}
                    </p>
                    <form method="POST" action="{{ route('admin.locations.assign') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="user_id" :value="__('User')" />
                            <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="location_id" :value="__('Location')" />
                            <select name="location_id" id="location_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('location_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="role_id" :value="__('Role')" />
                            <select name="role_id" id="role_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ ucfirst($role->slug) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ __('Assign role') }}</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
