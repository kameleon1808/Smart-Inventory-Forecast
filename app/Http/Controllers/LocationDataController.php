<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationDataController extends Controller
{
    public function show(Request $request): View
    {
        $location = $request->attributes->get('active_location');

        return view('location-data', [
            'location' => $location,
        ]);
    }
}
