<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->date('used_on');
            $table->decimal('quantity', 12, 3);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['location_id', 'menu_item_id', 'used_on']);
        });

        Schema::create('expected_consumption_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('expected_qty_in_base', 18, 4)->default(0);
            $table->timestamps();
            $table->unique(['location_id', 'date', 'item_id']);
            $table->index(['organization_id', 'location_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expected_consumption_dailies');
        Schema::dropIfExists('menu_item_usages');
    }
};
