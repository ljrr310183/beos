<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique('products_name_unique');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 4);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('tax_cost', 12, 4);
            $table->decimal('manufacturing_cost', 12, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
