<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('group', ['wallet', 'crypto', 'bank', 'agency_salary', 'code'])->index();
            $table->string('image')->nullable();
            $table->text('instructions')->nullable();
            $table->json('account_details')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 14, 4)->default(1);
            $table->decimal('min_amount', 14, 2)->default(1);
            $table->decimal('max_amount', 14, 2)->nullable();
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->boolean('requires_kyc')->default(false);
            $table->boolean('is_auto')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
