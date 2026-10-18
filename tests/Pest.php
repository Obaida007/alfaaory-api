<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Concurrency tests run work in separate PHP processes, so their data must really be committed.
pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Concurrency');

function buyer(string $balance = '0.00', array $attributes = []): User
{
    return User::factory()->withBalance($balance)->create($attributes);
}

function product(string $price = '10.00', array $attributes = []): Product
{
    return Product::factory()
        ->for(Category::factory())
        ->create(['price' => $price] + $attributes);
}
