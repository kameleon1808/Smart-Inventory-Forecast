<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Location;
use App\Domain\Organization;
use App\Domain\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $locations = Location::with(['organization', 'users'])->get();
        $users = User::orderBy('name')->get();
        $roles = Role::orderByDesc('slug')->get();
        $organization = Organization::first();

        return view('admin.locations', compact('locations', 'users', 'roles', 'organization'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        Location::create($data);

        return back()->with('status', 'location-created');
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $location = Location::with('organization')->findOrFail($data['location_id']);
        $role = Role::findOrFail($data['role_id']);

        $user->organizations()->syncWithoutDetaching([$location->organization_id]);
        $user->locations()->syncWithoutDetaching([
            $location->id => ['role_id' => $role->id],
        ]);

        $request->session()->put('active_location_id', $location->id);

        return back()->with('status', 'location-assigned');
    }
}
