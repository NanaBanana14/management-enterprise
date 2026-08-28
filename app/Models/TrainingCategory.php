<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCategory extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name'];

    public function programs(): HasMany
    {
        return $this->hasMany(TrainingProgram::class);
    }
}
