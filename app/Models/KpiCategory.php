<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiCategory extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name', 'description'];

    public function kpis(): HasMany
    {
        return $this->hasMany(Kpi::class);
    }
}
