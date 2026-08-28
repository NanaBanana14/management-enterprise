<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'name', 'unit', 'price', 'is_active'];

    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function totalStock(): float
    {
        return (float) $this->stocks()->sum('quantity');
    }
}
