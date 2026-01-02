<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Item;
use App\Domain\Recipes\MenuItem;
use App\Services\RecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function create(MenuItem $menuItem): View
    {
        $organization = request()->attributes->get('active_organization');

        $items = Item::with('baseUnit')
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        $menuItem->load('recipeVersions.ingredients');

        return view('recipes.create', [
            'menuItem' => $menuItem,
            'items' => $items,
        ]);
    }

    public function store(Request $request, MenuItem $menuItem, RecipeService $recipes): RedirectResponse
    {
        $data = $request->validate([
            'valid_from' => ['required', 'date'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.item_id' => ['required', 'distinct', 'exists:items,id'],
            'ingredients.*.unit_id' => ['required', 'exists:units,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        try {
            $recipes->createVersion($menuItem, $data['valid_from'], $data['ingredients']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors(['valid_from' => $e->getMessage()])->withInput();
        }

        return redirect()->route('recipes.create', $menuItem)->with('status', 'recipe-version-created');
    }
}
