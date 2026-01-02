<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('severity', 20)->default('medium');
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->date('happened_on');
            $table->decimal('metric_value', 18, 4);
            $table->decimal('threshold_value', 18, 4)->nullable();
            $table->string('status', 20)->default('OPEN');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'location_id', 'type', 'item_id', 'happened_on'], 'anomaly_unique_day');
            $table->index(['location_id', 'status']);
        });

        Schema::create('anomaly_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anomaly_id')->constrained('anomalies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('anomaly_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->decimal('absolute_threshold', 18, 4)->nullable();
            $table->decimal('percent_threshold', 8, 2)->nullable();
            $table->integer('count_threshold')->nullable();
            $table->string('severity', 20)->default('medium');
            $table->timestamps();

            $table->unique(['organization_id', 'location_id', 'type', 'item_id', 'category_id'], 'anomaly_threshold_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_thresholds');
        Schema::dropIfExists('anomaly_comments');
        Schema::dropIfExists('anomalies');
    }
};
