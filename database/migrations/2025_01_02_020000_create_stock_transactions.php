<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('status', 20)->default('POSTED');
            $table->timestamp('happened_at');
            $table->string('reference')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'location_id', 'warehouse_id']);
            $table->index(['type', 'status']);
        });

        Schema::create('stock_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('quantity_in_base', 18, 4);
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->timestamps();

            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transaction_lines');
        Schema::dropIfExists('stock_transactions');
    }
};
