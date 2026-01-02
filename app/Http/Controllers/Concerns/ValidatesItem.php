<?php

namespace App\Http\Controllers\Concerns;

use App\Domain\Inventory\Item;
use App\Domain\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait ValidatesItem
{
    /**
     * @return array<string, mixed>
     */
    protected function validateItem(Request $request, Organization $organization, ?Item $item = null): array
    {
        $itemId = $item?->id;

        return $request->validate([
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items')->where('organization_id', $organization->id)->ignore($itemId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:item_categories,id'],
            'base_unit_id' => ['required', 'exists:units,id'],
            'pack_size' => ['required', 'numeric', 'min:0.001'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'safety_stock' => ['required', 'numeric', 'min:0'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);
    }
}
