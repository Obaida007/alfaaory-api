<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 14, 2);
            $table->decimal('cost', 14, 2)->default(0);
            $table->enum('delivery_type', ['auto', 'manual', 'code'])->default('manual');
            $table->boolean('requires_player_id')->default(true);
            $table->boolean('supports_name_check')->default(false);
            $table->json('extra_fields')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_product_id')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'is_active', 'sort']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
