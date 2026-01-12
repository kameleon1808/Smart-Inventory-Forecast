<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\ExpectedConsumptionDaily;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpectedConsumptionReportController extends Controller
{
    public function index(Request $request): View
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $fromInput = $request->input('from', now()->toDateString());
        $toInput = $request->input('to', $fromInput);

        $fromDate = Carbon::parse($fromInput)->toDateString();
        $toDate = Carbon::parse($toInput ?: $fromInput)->toDateString();

        if (Carbon::parse($toDate)->lt(Carbon::parse($fromDate))) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $records = ExpectedConsumptionDaily::with('item')
            ->where('organization_id', $organization->id)
            ->where('location_id', $location->id)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->orderBy('date')
            ->orderBy('item_id')
            ->get()
            ->groupBy('date');

        return view('reports/expected-consumption', [
            'records' => $records,
            'from' => $fromDate,
            'to' => $toDate,
        ]);
    }
}
