<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('params');
            $table->string('external_job_id')->nullable();
            $table->timestamps();
        });

        Schema::create('forecast_result_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('predicted_qty_in_base', 18, 4);
            $table->decimal('lower', 18, 4);
            $table->decimal('upper', 18, 4);
            $table->string('model_version')->default('baseline');
            $table->timestamps();

            $table->unique(['organization_id', 'location_id', 'item_id', 'date'], 'forecast_daily_unique');
            $table->index(['location_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_result_dailies');
        Schema::dropIfExists('forecast_jobs');
    }
};
