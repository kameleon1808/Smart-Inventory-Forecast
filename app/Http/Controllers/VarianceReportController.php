<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Item;
use App\Services\VarianceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VarianceReportController extends Controller
{
    public function index(Request $request, VarianceReportService $service): View
    {
        $organization = $request->attributes->get('active_organization');
        $location = $request->attributes->get('active_location');

        $from = $request->input('from', now()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $warehouseId = $request->input('warehouse_id');

        $report = $service->calculate($organization->id, $location->id, $from, $to, $warehouseId ? (int) $warehouseId : null);

        $items = Item::where('organization_id', $organization->id)->pluck('name', 'id');

        return view('reports.variance', [
            'rows' => $report,
            'items' => $items,
            'from' => $from,
            'to' => $to,
            'warehouse_id' => $warehouseId,
        ]);
    }
}
