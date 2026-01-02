<?php

namespace App\Http\Controllers;

use App\Domain\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationContextController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $locationId = (int) $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ])['location_id'];

        $location = $user->locations()->where('locations.id', $locationId)->first();

        abort_unless($location, 403);

        $request->session()->put('active_location_id', $location->id);

        return back()->with('status', 'location-updated');
    }
}
