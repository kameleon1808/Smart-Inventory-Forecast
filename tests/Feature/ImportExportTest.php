<?php

namespace Tests\Feature;

use App\Domain\ImportJob;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\Unit;
use App\Jobs\ImportCsvJob;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
        Storage::fake();
    }

    public function test_import_dispatches_job_and_dry_run(): void
    {
        Bus::fake();
        $content = "sku,name,category,base_unit,pack_size,min_stock,safety_stock,lead_time_days,shelf_life_days,is_active\nSKU1,Coffee Beans,Coffee,kg,1,1,2,3,,true";
        $file = tmpfile();
        fwrite($file, $content);
        $meta = stream_get_meta_data($file);

        $response = $this->actingAs($this->admin())
            ->post(route('import.run'), [
                'type' => 'items',
                'file' => new \Illuminate\Http\UploadedFile($meta['uri'], 'items.csv', null, null, true),
                'dry_run' => 1,
            ]);

        $response->assertRedirect();

        Bus::assertDispatched(ImportCsvJob::class);
    }

    public function test_export_balances_returns_csv(): void
    {
        $locationId = \App\Domain\Location::first()->id;

        $response = $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $locationId])
            ->post(route('export.run'), [
                'type' => 'balances',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    protected function admin()
    {
        return \App\Models\User::where('email', 'admin@example.com')->firstOrFail();
    }
}
