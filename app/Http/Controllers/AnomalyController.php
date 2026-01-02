<?php

namespace App\Http\Controllers;

use App\Domain\Anomaly\Anomaly;
use App\Domain\Anomaly\AnomalyComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnomalyController extends Controller
{
    public function index(Request $request): View
    {
        $location = $request->attributes->get('active_location');

        $filters = [
            'status' => $request->input('status'),
            'severity' => $request->input('severity'),
            'type' => $request->input('type'),
        ];

        $anomalies = Anomaly::with('item')
            ->where('location_id', $location->id)
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['severity'], fn ($q) => $q->where('severity', $filters['severity']))
            ->when($filters['type'], fn ($q) => $q->where('type', $filters['type']))
            ->orderByDesc('happened_on')
            ->paginate(15)
            ->withQueryString();

        return view('anomalies.index', [
            'anomalies' => $anomalies,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Anomaly $anomaly): View
    {
        $location = $request->attributes->get('active_location');
        abort_if($anomaly->location_id !== $location->id, 403);

        $anomaly->load('item', 'comments.user');

        return view('anomalies.show', [
            'anomaly' => $anomaly,
        ]);
    }

    public function addComment(Request $request, Anomaly $anomaly): RedirectResponse
    {
        $location = $request->attributes->get('active_location');
        abort_if($anomaly->location_id !== $location->id, 403);

        $data = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        AnomalyComment::create([
            'anomaly_id' => $anomaly->id,
            'user_id' => $request->user()->id,
            'comment' => $data['comment'],
        ]);

        return back()->with('status', 'comment-added');
    }

    public function updateStatus(Request $request, Anomaly $anomaly): RedirectResponse
    {
        $location = $request->attributes->get('active_location');
        abort_if($anomaly->location_id !== $location->id, 403);

        $this->authorize('resolve-anomalies');

        $data = $request->validate([
            'status' => ['required', Rule::in([
                Anomaly::STATUS_OPEN,
                Anomaly::STATUS_INVESTIGATING,
                Anomaly::STATUS_RESOLVED,
                Anomaly::STATUS_FALSE_POSITIVE,
            ])],
        ]);

        $anomaly->update(['status' => $data['status']]);

        return back()->with('status', 'status-updated');
    }
}
