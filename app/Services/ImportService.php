<?php

namespace App\Services;

use App\Domain\Anomaly\AnomalyThreshold;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use App\Domain\Inventory\Unit;
use App\Domain\Inventory\UnitConversion;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\StockTransactionLine;
use App\Domain\Inventory\StockCount;
use App\Domain\Inventory\StockCountLine;
use App\Domain\Menu\MenuItem;
use App\Domain\Menu\RecipeIngredient;
use App\Domain\Menu\RecipeVersion;
use App\Domain\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class ImportService
{
    public const TYPE_ITEMS = 'items';
    public const TYPE_UNIT_CONVERSIONS = 'unit_conversions';
    public const TYPE_RECIPES = 'recipes';
    public const TYPE_OPENING_STOCK = 'opening_stock';

    /**
     * @return array{errors: array<int, string>, processed: int}
     */
    public function import(string $type, string $path, bool $dryRun, User $user): array
    {
        return match ($type) {
            self::TYPE_ITEMS => $this->importItems($path, $dryRun),
            self::TYPE_UNIT_CONVERSIONS => $this->importUnitConversions($path, $dryRun),
            self::TYPE_RECIPES => $this->importRecipes($path, $dryRun),
            self::TYPE_OPENING_STOCK => $this->importOpeningStock($path, $dryRun, $user),
            default => throw new RuntimeException('Unsupported import type'),
        };
    }

    private function rows(string $path): Collection
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $rows = collect();
        foreach ($file as $row) {
            if ($row === [null] || $row === false) {
                continue;
            }
            $rows->push(array_map('trim', $row));
        }

        return $rows;
    }

    private function importItems(string $path, bool $dryRun): array
    {
        $rows = $this->rows($path);
        $headers = ['sku','name','category','base_unit','pack_size','min_stock','safety_stock','lead_time_days','shelf_life_days','is_active'];
        $errors = [];
        $processed = 0;

        foreach ($rows as $index => $row) {
            if (count($row) < count($headers)) {
                $errors[] = "Row ".($index + 1)." has insufficient columns.";
                continue;
            }

            $data = array_combine($headers, $row);
            $category = ItemCategory::firstOrCreate(['name' => $data['category']]);
            $unit = Unit::where('slug', $data['base_unit'])->orWhere('name', $data['base_unit'])->first();

            if (! $unit) {
                $errors[] = "Row ".($index + 1).": base unit not found.";
                continue;
            }

            if ($dryRun) {
                $processed++;
                continue;
            }

            Item::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'organization_id' => 1,
                    'category_id' => $category->id,
                    'base_unit_id' => $unit->id,
                    'name' => $data['name'],
                    'pack_size' => (float) $data['pack_size'],
                    'min_stock' => (float) $data['min_stock'],
                    'safety_stock' => (float) $data['safety_stock'],
                    'lead_time_days' => (int) $data['lead_time_days'],
                    'shelf_life_days' => $data['shelf_life_days'] !== '' ? (int) $data['shelf_life_days'] : null,
                    'is_active' => filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN),
                ]
            );
            $processed++;
        }

        return ['errors' => $errors, 'processed' => $processed];
    }

    private function importUnitConversions(string $path, bool $dryRun): array
    {
        $rows = $this->rows($path);
        $headers = ['from_unit','to_unit','factor'];
        $errors = [];
        $processed = 0;

        foreach ($rows as $index => $row) {
            if (count($row) < count($headers)) {
                $errors[] = "Row ".($index + 1)." has insufficient columns.";
                continue;
            }

            $data = array_combine($headers, $row);
            $from = Unit::where('slug', $data['from_unit'])->orWhere('name', $data['from_unit'])->first();
            $to = Unit::where('slug', $data['to_unit'])->orWhere('name', $data['to_unit'])->first();

            if (! $from || ! $to) {
                $errors[] = "Row ".($index + 1).": units not found.";
                continue;
            }

            if ($dryRun) {
                $processed++;
                continue;
            }

            UnitConversion::updateOrCreate(
                ['from_unit_id' => $from->id, 'to_unit_id' => $to->id],
                ['factor' => (float) $data['factor']]
            );
            $processed++;
        }

        return ['errors' => $errors, 'processed' => $processed];
    }

    private function importRecipes(string $path, bool $dryRun): array
    {
        $rows = $this->rows($path);
        $headers = ['menu_item','valid_from','ingredient_sku','quantity','unit'];
        $errors = [];
        $processed = 0;

        $grouped = [];
        foreach ($rows as $index => $row) {
            if (count($row) < count($headers)) {
                $errors[] = "Row ".($index + 1)." has insufficient columns.";
                continue;
            }
            $data = array_combine($headers, $row);
            $grouped[$data['menu_item'].'|'.$data['valid_from']][] = $data;
        }

        foreach ($grouped as $key => $ingredients) {
            [$menuName, $validFrom] = explode('|', $key, 2);
            $menuItem = MenuItem::firstOrCreate(['name' => $menuName], ['is_active' => true]);
            $versionData = [
                'menu_item_id' => $menuItem->id,
                'valid_from' => Carbon::parse($validFrom)->toDateString(),
                'valid_to' => null,
            ];

            if ($dryRun) {
                $processed += count($ingredients);
                continue;
            }

            $version = RecipeVersion::create($versionData);

            foreach ($ingredients as $ingredient) {
                $item = Item::where('sku', $ingredient['ingredient_sku'])->first();
                $unit = Unit::where('slug', $ingredient['unit'])->orWhere('name', $ingredient['unit'])->first();

                if (! $item || ! $unit) {
                    $errors[] = "Missing item/unit for menu {$menuName}";
                    continue;
                }

                RecipeIngredient::create([
                    'recipe_version_id' => $version->id,
                    'item_id' => $item->id,
                    'quantity' => (float) $ingredient['quantity'],
                    'unit_id' => $unit->id,
                    'quantity_in_base' => (float) $ingredient['quantity'], // naive
                ]);
                $processed++;
            }
        }

        return ['errors' => $errors, 'processed' => $processed];
    }

    private function importOpeningStock(string $path, bool $dryRun, User $user): array
    {
        $rows = $this->rows($path);
        $headers = ['warehouse','item_sku','quantity','unit','counted_at'];
        $errors = [];
        $processed = 0;

        foreach ($rows as $index => $row) {
            if (count($row) < count($headers)) {
                $errors[] = "Row ".($index + 1)." has insufficient columns.";
                continue;
            }

            $data = array_combine($headers, $row);
            $warehouse = Warehouse::where('name', $data['warehouse'])->first();
            $item = Item::where('sku', $data['item_sku'])->first();
            $unit = Unit::where('slug', $data['unit'])->orWhere('name', $data['unit'])->first();

            if (! $warehouse || ! $item || ! $unit) {
                $errors[] = "Row ".($index + 1).": warehouse/item/unit missing.";
                continue;
            }

            $countedAt = Carbon::parse($data['counted_at']);

            if ($dryRun) {
                $processed++;
                continue;
            }

            DB::transaction(function () use ($warehouse, $item, $unit, $data, $countedAt, $user): void {
                $count = StockCount::create([
                    'organization_id' => $warehouse->organization_id,
                    'location_id' => $warehouse->location_id,
                    'warehouse_id' => $warehouse->id,
                    'status' => StockCount::STATUS_DRAFT,
                    'counted_at' => $countedAt,
                    'created_by' => $user->id,
                ]);

                StockCountLine::create([
                    'stock_count_id' => $count->id,
                    'item_id' => $item->id,
                    'counted_quantity_in_base' => (float) $data['quantity'],
                ]);

                $transaction = StockTransaction::create([
                    'organization_id' => $warehouse->organization_id,
                    'location_id' => $warehouse->location_id,
                    'warehouse_id' => $warehouse->id,
                    'type' => StockTransaction::TYPE_STOCK_COUNT_ADJUSTMENT,
                    'status' => StockTransaction::STATUS_POSTED,
                    'happened_at' => $countedAt,
                    'reference' => 'Opening stock import',
                    'created_by' => $user->id,
                ]);

                StockTransactionLine::create([
                    'stock_transaction_id' => $transaction->id,
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'quantity' => (float) $data['quantity'],
                    'quantity_in_base' => (float) $data['quantity'],
                    'unit_cost' => null,
                ]);
            });
            $processed++;
        }

        return ['errors' => $errors, 'processed' => $processed];
    }
}
