<?php

namespace App\Http\Controllers;

use App\Domain\Recipes\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function index(Request $request): View
    {
        $organization = $request->attributes->get('active_organization');

        $items = MenuItem::where('organization_id', $organization->id)
            ->orderBy('name')
            ->paginate(10);

        return view('menu-items.index', ['items' => $items]);
    }

    public function create(): View
    {
        return view('menu-items.form', ['menuItem' => new MenuItem()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $request->attributes->get('active_organization');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['organization_id'] = $organization->id;
        $data['is_active'] = $request->boolean('is_active', true);

        MenuItem::create($data);

        return redirect()->route('menu-items.index')->with('status', 'menu-item-created');
    }

    public function edit(MenuItem $menuItem): View
    {
        return view('menu-items.form', ['menuItem' => $menuItem]);
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', false);

        $menuItem->update($data);

        return redirect()->route('menu-items.index')->with('status', 'menu-item-updated');
    }
}
