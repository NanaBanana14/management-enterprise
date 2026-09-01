<?php

namespace App\Models;

use App\Enums\TrainingMaterialType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TrainingMaterial extends Model
{
    protected $fillable = ['training_program_id', 'title', 'type', 'body', 'video_url', 'file_path', 'order'];

    protected function casts(): array
    {
        return [
            'type' => TrainingMaterialType::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}
