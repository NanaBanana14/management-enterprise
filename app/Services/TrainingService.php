<?php

namespace App\Services;

use App\Enums\TrainingAudience;
use App\Enums\TrainingMaterialType;
use App\Models\Department;
use App\Models\TrainingCategory;
use App\Models\TrainingMaterial;
use App\Models\TrainingProgram;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrainingService
{
    public function createProgram(
        TrainingCategory $category,
        string $name,
        TrainingAudience $audience,
        ?Department $department,
        ?string $provider,
        ?int $durationHours,
        ?string $description,
    ): TrainingProgram {
        return TrainingProgram::create([
            'training_category_id' => $category->id,
            'department_id' => $department?->id,
            'name' => $name,
            'audience' => $audience,
            'provider' => $provider,
            'duration_hours' => $durationHours,
            'description' => $description,
        ]);
    }

    public function addMaterial(
        TrainingProgram $program,
        string $title,
        TrainingMaterialType $type,
        ?string $body,
        ?string $videoUrl,
        ?UploadedFile $file,
        int $order,
    ): TrainingMaterial {
        return DB::transaction(function () use ($program, $title, $type, $body, $videoUrl, $file, $order) {
            $filePath = $type === TrainingMaterialType::Document && $file
                ? $file->store('training/materials', 'public')
                : null;

            return $program->materials()->create([
                'title' => $title,
                'type' => $type,
                'body' => $type === TrainingMaterialType::Text ? $body : null,
                'video_url' => $type === TrainingMaterialType::Video ? $videoUrl : null,
                'file_path' => $filePath,
                'order' => $order,
            ]);
        });
    }

    public function updateMaterial(
        TrainingMaterial $material,
        string $title,
        TrainingMaterialType $type,
        ?string $body,
        ?string $videoUrl,
        ?UploadedFile $file,
    ): TrainingMaterial {
        return DB::transaction(function () use ($material, $title, $type, $body, $videoUrl, $file) {
            $filePath = $material->file_path;

            if ($type === TrainingMaterialType::Document && $file) {
                if ($material->file_path) {
                    Storage::disk('public')->delete($material->file_path);
                }

                $filePath = $file->store('training/materials', 'public');
            } elseif ($type !== TrainingMaterialType::Document && $material->file_path) {
                Storage::disk('public')->delete($material->file_path);
                $filePath = null;
            }

            $material->update([
                'title' => $title,
                'type' => $type,
                'body' => $type === TrainingMaterialType::Text ? $body : null,
                'video_url' => $type === TrainingMaterialType::Video ? $videoUrl : null,
                'file_path' => $filePath,
            ]);

            return $material;
        });
    }

    public function deleteMaterial(TrainingMaterial $material): void
    {
        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();
    }
}
