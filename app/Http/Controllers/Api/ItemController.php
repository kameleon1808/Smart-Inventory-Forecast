<?php

namespace App\Http\Controllers\Api;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use App\Domain\Inventory\Unit;
use App\Http\Controllers\Concerns\ValidatesItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    use ValidatesItem;

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('active_organization');

        $items = Item::with(['category', 'baseUnit'])
            ->forOrganization($organization)
            ->search($request->string('search'))
            ->category($request->integer('category_id'))
            ->active($request->has('is_active') ? $request->boolean('is_active') : null)
            ->orderBy('name')
            ->paginate(15);

        return response()->json($items);
    }

    public function show(Request $request, Item $item): JsonResponse
    {
        $organization = $request->attributes->get('active_organization');
        abort_unless($item->organization_id === $organization->id, 404);

        $item->load(['category', 'baseUnit']);

        return response()->json($item);
    }

    public function store(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('active_organization');
        $data = $this->validateItem($request, $organization);
        $data['organization_id'] = $organization->id;

        $item = Item::create($data);

        return response()->json($item->fresh(['category', 'baseUnit']), 201);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $organization = $request->attributes->get('active_organization');
        abort_unless($item->organization_id === $organization->id, 404);

        $data = $this->validateItem($request, $organization, $item);
        $data['organization_id'] = $organization->id;

        $item->update($data);

        return response()->json($item->fresh(['category', 'baseUnit']));
    }
}
