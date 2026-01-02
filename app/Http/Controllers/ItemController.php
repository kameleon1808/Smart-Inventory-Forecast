<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use App\Domain\Inventory\Unit;
use App\Http\Controllers\Concerns\ValidatesItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    use ValidatesItem;

    public function index(Request $request): View
    {
        $organization = $request->attributes->get('active_organization');

        $items = Item::with(['category', 'baseUnit'])
            ->forOrganization($organization)
            ->search($request->string('search'))
            ->category($request->integer('category_id'))
            ->active($request->has('is_active') ? $request->boolean('is_active') : null)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = ItemCategory::orderBy('name')->get();

        return view('items.index', [
            'items' => $items,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'is_active']),
        ]);
    }

    public function create(): View
    {
        return view('items.form', [
            'item' => new Item(),
            'categories' => ItemCategory::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $request->attributes->get('active_organization');
        $data = $this->validateItem($request, $organization);
        $data['organization_id'] = $organization->id;

        $item = Item::create($data);

        return redirect()->route('items.edit', $item)->with('status', 'item-created');
    }

    public function edit(Request $request, Item $item): View
    {
        $organization = $request->attributes->get('active_organization');
        abort_unless($item->organization_id === $organization->id, 404);

        return view('items.form', [
            'item' => $item,
            'categories' => ItemCategory::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $organization = $request->attributes->get('active_organization');
        abort_unless($item->organization_id === $organization->id, 404);
        $data = $this->validateItem($request, $organization, $item);
        $data['organization_id'] = $organization->id;

        $item->update($data);

        return redirect()->route('items.edit', $item)->with('status', 'item-updated');
    }
}
