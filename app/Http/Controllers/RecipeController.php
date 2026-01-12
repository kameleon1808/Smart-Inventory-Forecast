<?php

namespace App\Http\Controllers;

use App\Domain\Inventory\Item;
use App\Domain\Recipes\MenuItem;
use App\Services\AuditLogger;
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

        $menuItem->load('recipeVersions.ingredients.item', 'recipeVersions.ingredients.unit');

        $itemsForJs = $items->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'base_unit_id' => $item->baseUnit?->id,
            'base_unit_label' => trim(($item->baseUnit?->name ?? '').' '.($item->baseUnit?->symbol ? '('.$item->baseUnit->symbol.')' : '')),
        ])->values();

        $initialIngredients = collect(old('ingredients', []))
            ->map(fn ($ing) => [
                'item_id' => $ing['item_id'] ?? '',
                'unit_id' => $ing['unit_id'] ?? '',
                'quantity' => $ing['quantity'] ?? '',
                'unit_label' => $ing['unit_label'] ?? '',
            ])->filter(fn ($ing) => $ing['item_id'] !== '' || $ing['quantity'] !== '')->values();

        if ($initialIngredients->isEmpty() && $menuItem->recipeVersions->isNotEmpty()) {
            $latest = $menuItem->recipeVersions->sortByDesc('valid_from')->first();
            $initialIngredients = $latest?->ingredients->map(fn ($ing) => [
                'item_id' => $ing->item_id,
                'unit_id' => $ing->unit_id,
                'quantity' => (float) $ing->quantity,
                'unit_label' => trim(($ing->unit?->name ?? '').($ing->unit?->symbol ? ' ('.$ing->unit->symbol.')' : '')),
            ]) ?? collect();
        }

        $latestVersion = $menuItem->recipeVersions->sortByDesc('valid_from')->first();
        $defaultValidFrom = old('valid_from', $latestVersion?->valid_from?->copy()->addDay()->format('Y-m-d') ?? now()->toDateString());

        return view('recipes.create', [
            'menuItem' => $menuItem,
            'items' => $items,
            'initialIngredients' => collect([['item_id' => '', 'unit_id' => '', 'quantity' => '']])
                ->merge($initialIngredients)
                ->values(),
            'latestVersion' => $latestVersion,
            'defaultValidFrom' => $defaultValidFrom,
            'itemsForJs' => $itemsForJs,
        ]);
    }

    public function store(Request $request, MenuItem $menuItem, RecipeService $recipes, AuditLogger $audit): RedirectResponse
    {
        $filteredIngredients = collect($request->input('ingredients', []))
            ->filter(fn ($ing) => isset($ing['item_id'], $ing['unit_id'], $ing['quantity']) && $ing['item_id'] !== '' && $ing['unit_id'] !== '' && $ing['quantity'] !== '')
            ->values()
            ->all();

        $request->merge(['ingredients' => $filteredIngredients]);

        $data = $request->validate([
            'valid_from' => ['required', 'date'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.item_id' => ['required', 'distinct', 'exists:items,id'],
            'ingredients.*.unit_id' => ['required', 'exists:units,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        try {
            $version = $recipes->createVersion($menuItem, $data['valid_from'], $data['ingredients']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors(['valid_from' => $e->getMessage()])->withInput();
        }

        $audit->log('recipe.version.created', $version ?? $menuItem, null, ['menu_item' => $menuItem->id, 'valid_from' => $data['valid_from']]);

        return redirect()->route('recipes.create', $menuItem)->with('status', 'recipe-version-created');
    }
}
