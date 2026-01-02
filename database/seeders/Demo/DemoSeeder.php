<?php

namespace Database\Seeders\Demo;

use App\Domain\Anomaly\AnomalyThreshold;
use App\Domain\Forecast\ForecastResultDaily;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use App\Domain\Inventory\Unit;
use App\Domain\Inventory\UnitConversion;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\StockTransactionLine;
use App\Domain\Inventory\StockCount;
use App\Domain\Inventory\StockCountLine;
use App\Domain\Organization;
use App\Domain\Procurement\PurchaseOrder;
use App\Domain\Procurement\PurchaseOrderLine;
use App\Domain\Recipes\MenuItem;
use App\Domain\Recipes\MenuItemUsage;
use App\Domain\Recipes\RecipeVersion;
use App\Domain\Role;
use App\Domain\Warehouse;
use App\Domain\Location;
use App\Models\User;
use App\Services\AnomalyDetectionService;
use App\Services\ExpectedConsumptionService;
use App\Services\PurchaseReceivingService;
use App\Services\RecipeService;
use App\Services\UnitConversionService;
use Carbon\Carbon;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create();
        $faker->seed(12345);

        // Ensure base seeders run for core data
        $this->call([
            RbacSeeder::class,
            InventorySeeder::class,
        ]);

        $organization = Organization::firstOrCreate(['name' => 'Smart Inventory Org']);

        [$locations, $warehouses] = $this->seedLocationsAndWarehouses($organization);
        $this->seedUsers($organization, $locations);

        $units = Unit::all();
        $items = $this->seedItems($organization, $faker, $units);
        $menuItems = $this->seedMenuItemsAndRecipes($organization, $items, $units, $faker);

        $this->seedThresholds($organization, $locations);

        $this->seedLedger($organization, $locations, $warehouses, $items, $faker);
        $this->seedStockCounts($organization, $warehouses, $items, $faker);

        $this->seedUsageAndExpected($organization, $locations, $menuItems, $faker);
        $this->seedProcurement($organization, $warehouses, $items, $faker);
        $this->seedForecasts($organization, $locations, $items, $faker);

        $this->generateAnomalies($organization, $locations);
    }

    private function seedLocationsAndWarehouses(Organization $organization): array
    {
        $locations = collect([
            'Downtown',
            'Riverside',
            'Mall',
        ])->map(fn ($name) => Location::firstOrCreate([
            'name' => $name,
            'organization_id' => $organization->id,
        ]));

        $warehouses = collect();

        foreach ($locations as $location) {
            $warehouses->push(Warehouse::firstOrCreate([
                'name' => "{$location->name} - Bar",
                'organization_id' => $organization->id,
                'location_id' => $location->id,
            ]));
            $warehouses->push(Warehouse::firstOrCreate([
                'name' => "{$location->name} - Kitchen",
                'organization_id' => $organization->id,
                'location_id' => $location->id,
            ]));
        }

        return [$locations, $warehouses];
    }

    private function seedUsers(Organization $organization, $locations): void
    {
        $roles = Role::all()->keyBy('slug');

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $managers = collect();
        $waiters = collect();
        $viewers = collect();

        foreach ($locations as $index => $location) {
            $manager = User::firstOrCreate(
                ['email' => "manager{$index}@example.com"],
                ['name' => "Manager {$location->name}", 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $managers->push($manager);

            for ($i = 1; $i <= 2; $i++) {
                $waiter = User::firstOrCreate(
                    ['email' => "waiter{$location->id}{$i}@example.com"],
                    ['name' => "Waiter {$location->name} {$i}", 'password' => Hash::make('password'), 'email_verified_at' => now()]
                );
                $waiters->push($waiter);
            }
        }

        for ($i = 1; $i <= 2; $i++) {
            $viewer = User::firstOrCreate(
                ['email' => "viewer{$i}@example.com"],
                ['name' => "Viewer {$i}", 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $viewers->push($viewer);
        }

        $users = collect([$admin])->merge($managers)->merge($waiters)->merge($viewers);
        $users->each(fn (User $user) => $user->organizations()->syncWithoutDetaching([$organization->id]));

        $admin->locations()->syncWithoutDetaching($locations->mapWithKeys(fn ($loc) => [$loc->id => ['role_id' => $roles[Role::ADMIN]->id]])->toArray());

        foreach ($locations as $idx => $loc) {
            $manager = $managers[$idx];
            $manager->locations()->syncWithoutDetaching([$loc->id => ['role_id' => $roles[Role::MANAGER]->id]]);

            $waitersForLoc = $waiters->splice(0, 2);
            foreach ($waitersForLoc as $waiter) {
                $waiter->locations()->syncWithoutDetaching([$loc->id => ['role_id' => $roles[Role::WAITER]->id]]);
            }
        }

        foreach ($viewers as $viewer) {
            $viewer->locations()->syncWithoutDetaching([$locations->first()->id => ['role_id' => $roles[Role::VIEWER]->id]]);
        }
    }

    private function seedItems(Organization $organization, $faker, $units)
    {
        $categories = ItemCategory::all();
        $baseUnits = $units->whereIn('slug', ['g', 'kg', 'ml', 'l', 'pcs'])->values();

        $items = collect();
        for ($i = 0; $i < 80; $i++) {
            $unit = $faker->randomElement($baseUnits);
            $category = $faker->randomElement($categories);
            $items->push(Item::factory()->create([
                'organization_id' => $organization->id,
                'category_id' => $category->id,
                'base_unit_id' => $unit->id,
                'pack_size' => $faker->randomFloat(2, 1, 10),
                'min_stock' => $faker->randomFloat(2, 2, 30),
                'safety_stock' => $faker->randomFloat(2, 1, 20),
                'lead_time_days' => $faker->numberBetween(1, 7),
                'is_active' => true,
            ]));
        }

        return $items;
    }

    private function seedMenuItemsAndRecipes(Organization $organization, $items, $units, $faker)
    {
        $menuItems = collect();
        $recipeService = app(RecipeService::class);

        $validFrom = Carbon::now()->subDays(60)->toDateString();

        for ($i = 0; $i < 30; $i++) {
            $menu = MenuItem::create([
                'organization_id' => $organization->id,
                'name' => 'Menu Item '.($i + 1),
                'is_active' => true,
            ]);

            $ingredients = [];
            $count = $faker->numberBetween(3, 6);
            $ingredientItems = $items->random($count);
            foreach ($ingredientItems as $ingredientItem) {
                $ingredients[] = [
                    'item_id' => $ingredientItem->id,
                    'unit_id' => $ingredientItem->base_unit_id,
                    'quantity' => $faker->randomFloat(2, 0.05, 1.5),
                ];
            }

            $recipeService->createVersion($menu, $validFrom, $ingredients);
            $menuItems->push($menu);
        }

        return $menuItems;
    }

    private function seedThresholds(Organization $organization, $locations): void
    {
        foreach ($locations as $location) {
            AnomalyThreshold::updateOrCreate([
                'organization_id' => $organization->id,
                'location_id' => $location->id,
                'type' => 'variance_spike',
            ], [
                'percent_threshold' => 40,
                'severity' => 'medium',
            ]);

            AnomalyThreshold::updateOrCreate([
                'organization_id' => $organization->id,
                'location_id' => $location->id,
                'type' => 'waste_spike',
            ], [
                'absolute_threshold' => 5,
                'percent_threshold' => 50,
                'severity' => 'medium',
            ]);

            AnomalyThreshold::updateOrCreate([
                'organization_id' => $organization->id,
                'location_id' => $location->id,
                'type' => 'adjustment_count',
            ], [
                'count_threshold' => 2,
                'severity' => 'medium',
            ]);
        }
    }

    private function seedLedger(Organization $organization, $locations, $warehouses, $items, $faker): void
    {
        $start = Carbon::now()->subDays(30);

        foreach ($locations as $location) {
            $locWarehouses = $warehouses->where('location_id', $location->id)->values();
            $topItems = $items->random(8);

            $day = $start->copy();
            while ($day->lte(Carbon::now())) {
                foreach ($locWarehouses as $warehouse) {
                    // Weekly receipts
                    if ($day->isMonday() || $day->isThursday()) {
                        foreach ($topItems->random(5) as $item) {
                            $qty = $faker->randomFloat(2, 5, 30);
                            $this->createTransaction($organization, $location, $warehouse, $item, StockTransaction::TYPE_RECEIPT, $day, $qty, 'Supplier '.$faker->company());
                        }
                    }

                    // Daily waste small with occasional spike
                    foreach ($topItems->random(3) as $item) {
                        $isSpike = $faker->boolean(3);
                        $qty = $isSpike ? $faker->randomFloat(2, 8, 15) : $faker->randomFloat(2, 0.1, 2);
                        $this->createTransaction($organization, $location, $warehouse, $item, StockTransaction::TYPE_WASTE, $day, $qty, null, 'Waste');
                    }

                    // Occasional internal use
                    if ($faker->boolean(20)) {
                        $item = $topItems->random();
                        $qty = $faker->randomFloat(2, 0.5, 5);
                        $this->createTransaction($organization, $location, $warehouse, $item, StockTransaction::TYPE_INTERNAL_USE, $day, $qty, null, 'Internal use');
                    }

                    // Rare adjustment
                    if ($faker->boolean(5)) {
                        $item = $topItems->random();
                        $qty = $faker->randomFloat(2, -3, 3);
                        $this->createTransaction($organization, $location, $warehouse, $item, StockTransaction::TYPE_ADJUSTMENT, $day, $qty);
                    }
                }
                $day->addDay();
            }
        }
    }

    private function createTransaction(Organization $organization, Location $location, Warehouse $warehouse, Item $item, string $type, Carbon $day, float $qty, ?string $supplier = null, ?string $reason = null): void
    {
        $quantityInBase = $qty;
        if (in_array($type, [StockTransaction::TYPE_WASTE, StockTransaction::TYPE_INTERNAL_USE], true)) {
            $quantityInBase *= -1;
        }

        $transaction = StockTransaction::create([
            'organization_id' => $organization->id,
            'location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
            'type' => $type,
            'status' => StockTransaction::STATUS_POSTED,
            'happened_at' => $day->copy()->setTime(rand(8, 20), 0),
            'supplier_name' => $supplier,
            'reason' => $reason,
            'created_by' => User::first()->id,
        ]);

        StockTransactionLine::create([
            'stock_transaction_id' => $transaction->id,
            'item_id' => $item->id,
            'unit_id' => $item->base_unit_id,
            'quantity' => abs($qty),
            'quantity_in_base' => $quantityInBase,
            'unit_cost' => null,
        ]);
    }

    private function seedStockCounts(Organization $organization, $warehouses, $items, $faker): void
    {
        $service = app(\App\Services\StockCountService::class);

        foreach ($warehouses as $warehouse) {
            for ($i = 0; $i < 2; $i++) {
                $countDate = Carbon::now()->subDays(40 - $i * 20);
                $count = StockCount::create([
                    'organization_id' => $organization->id,
                    'location_id' => $warehouse->location_id,
                    'warehouse_id' => $warehouse->id,
                    'status' => StockCount::STATUS_DRAFT,
                    'counted_at' => $countDate,
                    'created_by' => User::first()->id,
                ]);

                $lines = [];
                foreach ($items->random(5) as $item) {
                    StockCountLine::create([
                        'stock_count_id' => $count->id,
                        'item_id' => $item->id,
                        'counted_quantity_in_base' => $faker->randomFloat(2, 5, 20),
                    ]);
                }

                $service->post($count);
            }
        }
    }

    private function seedUsageAndExpected(Organization $organization, $locations, $menuItems, $faker): void
    {
        $start = Carbon::now()->subDays(30);
        $expectedService = app(ExpectedConsumptionService::class);

        foreach ($locations as $location) {
            $day = $start->copy();
            while ($day->lte(Carbon::now())) {
                $isWeekend = in_array($day->dayOfWeekIso, [5, 6]);
                $eventBoost = $faker->boolean(5) ? 2 : 1;
                foreach ($menuItems->random(5) as $menu) {
                    $qty = $faker->numberBetween(5, 20);
                    if ($isWeekend) {
                        $qty *= 1.3;
                    }
                    $qty *= $eventBoost;

                    MenuItemUsage::create([
                        'organization_id' => $organization->id,
                        'location_id' => $location->id,
                        'menu_item_id' => $menu->id,
                        'used_on' => $day->toDateString(),
                        'quantity' => $qty,
                        'created_by' => User::first()->id,
                    ]);
                }
                $day->addDay();
            }

            $expectedService->recompute($organization->id, $location->id, $start->toDateString(), Carbon::now()->toDateString());
        }
    }

    private function seedProcurement(Organization $organization, $warehouses, $items, $faker): void
    {
        $receivingService = app(PurchaseReceivingService::class);
        $poStatuses = [
            PurchaseOrder::STATUS_DRAFT,
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            PurchaseOrder::STATUS_CLOSED,
        ];

        foreach ($warehouses as $warehouse) {
            for ($i = 0; $i < 4; $i++) {
                $status = $poStatuses[$i];
                $poStatusForCreate = in_array($status, [PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_CLOSED], true)
                    ? PurchaseOrder::STATUS_APPROVED
                    : $status;

                $po = PurchaseOrder::create([
                    'organization_id' => $organization->id,
                    'location_id' => $warehouse->location_id,
                    'warehouse_id' => $warehouse->id,
                    'supplier_name' => 'Supplier '.$faker->company(),
                    'status' => $poStatusForCreate,
                    'created_by' => User::first()->id,
                    'approved_by' => User::first()->id,
                    'approved_at' => now(),
                ]);

                $lines = [];
                foreach ($items->random(3) as $item) {
                    $qty = $faker->randomFloat(2, 10, 30);
                    $lines[] = PurchaseOrderLine::create([
                        'purchase_order_id' => $po->id,
                        'item_id' => $item->id,
                        'qty_ordered_in_base' => $qty,
                        'unit_id_display' => $item->base_unit_id,
                        'qty_display' => $qty,
                        'unit_cost_estimate' => $faker->randomFloat(2, 2, 10),
                    ]);
                }

                // Receive partially or fully depending on status
                if (in_array($status, [PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_CLOSED], true)) {
                    $receipts = $status === PurchaseOrder::STATUS_PARTIALLY_RECEIVED ? 1 : 2;
                    for ($r = 0; $r < $receipts; $r++) {
                        $linePayloads = [];
                        foreach ($lines as $line) {
                            $target = $line->qty_ordered_in_base;
                            $portion = $status === PurchaseOrder::STATUS_PARTIALLY_RECEIVED ? $target * 0.5 : $target * (($r === 0) ? 0.5 : 0.5);
                            $linePayloads[] = ['item_id' => $line->item_id, 'qty' => $portion];
                        }
                        $receivingService->receive($po, $linePayloads, User::first(), now()->toDateTimeString(), 'PO-'.$po->id);
                    }

                    if ($status === PurchaseOrder::STATUS_PARTIALLY_RECEIVED) {
                        $po->refresh();
                        $po->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
                    } else {
                        $po->refresh();
                        $po->update(['status' => PurchaseOrder::STATUS_CLOSED]);
                    }
                }
            }
        }
    }

    private function seedForecasts(Organization $organization, $locations, $items, $faker): void
    {
        $topItems = $items->take(30);
        $start = Carbon::now()->addDay();

        foreach ($locations as $location) {
            for ($i = 0; $i < 14; $i++) {
                $day = $start->copy()->addDays($i)->toDateString();
                foreach ($topItems as $item) {
                    ForecastResultDaily::updateOrCreate([
                        'organization_id' => $organization->id,
                        'location_id' => $location->id,
                        'item_id' => $item->id,
                        'date' => $day,
                    ], [
                        'predicted_qty_in_base' => $faker->randomFloat(2, 5, 20),
                        'lower' => $faker->randomFloat(2, 4, 18),
                        'upper' => $faker->randomFloat(2, 6, 24),
                        'model_version' => 'baseline',
                    ]);
                }
            }
        }
    }

    private function generateAnomalies(Organization $organization, $locations): void
    {
        $service = app(AnomalyDetectionService::class);
        $end = Carbon::now();
        $start = Carbon::now()->subDays(7);

        foreach ($locations as $location) {
            $service->detectForRange($organization->id, $location->id, $start, $end);
        }
    }
}
