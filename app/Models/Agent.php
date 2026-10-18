<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'region', 'phone', 'whatsapp', 'sort', 'is_active'])]
class Agent extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
