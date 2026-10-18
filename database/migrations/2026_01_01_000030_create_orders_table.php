<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('player_id')->nullable();
            $table->string('player_name')->nullable();
            $table->json('extra_data')->nullable();
            $table->decimal('unit_price', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected', 'refunded'])->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('delivered_code')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
