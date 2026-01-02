<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\ExpectedConsumptionDaily;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpectedConsumptionReportController extends Controller
{
    public function index(Request $request): View
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $from = $request->input('from', now()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $records = ExpectedConsumptionDaily::with('item')
            ->where('organization_id', $organization->id)
            ->where('location_id', $location->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('item_id')
            ->get()
            ->groupBy('date');

        return view('reports/expected-consumption', [
            'records' => $records,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
