<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodLockController extends Controller
{
    public function edit(Request $request): View
    {
        $location = $request->attributes->get('active_location');

        return view('period-lock.edit', [
            'location' => $location,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $location = $request->attributes->get('active_location');
        $data = $request->validate([
            'lock_before_date' => ['nullable', 'date'],
        ]);

        $location->update(['lock_before_date' => $data['lock_before_date'] ?? null]);

        return back()->with('status', 'lock-updated');
    }
}
