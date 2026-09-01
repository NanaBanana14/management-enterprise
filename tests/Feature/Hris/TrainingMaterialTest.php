<?php

namespace Tests\Feature\Hris;

use App\Enums\TrainingAudience;
use App\Enums\TrainingMaterialType;
use App\Models\Department;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_general_program_is_visible_regardless_of_department()
    {
        $category = TrainingCategory::create(['name' => 'Test Category']);
        $general = TrainingProgram::create(['training_category_id' => $category->id, 'name' => 'General Program', 'audience' => TrainingAudience::Staff->value]);
        $department = Department::factory()->create();

        $this->assertTrue(TrainingProgram::query()->visibleTo($department->id)->whereKey($general->id)->exists());
        $this->assertTrue(TrainingProgram::query()->visibleTo(null)->whereKey($general->id)->exists());
    }

    public function test_a_department_scoped_program_is_only_visible_to_its_own_department()
    {
        $category = TrainingCategory::create(['name' => 'Test Category']);
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $scoped = TrainingProgram::create([
            'training_category_id' => $category->id,
            'department_id' => $departmentA->id,
            'name' => 'Scoped Program',
            'audience' => TrainingAudience::Staff->value,
        ]);

        $this->assertTrue(TrainingProgram::query()->visibleTo($departmentA->id)->whereKey($scoped->id)->exists());
        $this->assertFalse(TrainingProgram::query()->visibleTo($departmentB->id)->whereKey($scoped->id)->exists());
    }

    public function test_a_text_material_stores_its_body()
    {
        $category = TrainingCategory::create(['name' => 'Test Category']);
        $program = TrainingProgram::create(['training_category_id' => $category->id, 'name' => 'Program', 'audience' => TrainingAudience::Staff->value]);

        $material = app(TrainingService::class)->addMaterial($program, 'Intro', TrainingMaterialType::Text, 'Some course text.', null, null, 0);

        $this->assertSame('Some course text.', $material->body);
        $this->assertNull($material->file_path);
    }

    public function test_a_document_material_is_stored_on_the_public_disk()
    {
        Storage::fake('public');

        $category = TrainingCategory::create(['name' => 'Test Category']);
        $program = TrainingProgram::create(['training_category_id' => $category->id, 'name' => 'Program', 'audience' => TrainingAudience::Staff->value]);
        $file = UploadedFile::fake()->create('handbook.pdf', 100, 'application/pdf');

        $material = app(TrainingService::class)->addMaterial($program, 'Handbook', TrainingMaterialType::Document, null, null, $file, 0);

        $this->assertNotNull($material->file_path);
        Storage::disk('public')->assertExists($material->file_path);
    }

    public function test_updating_a_material_replaces_its_file_and_deletes_the_old_one()
    {
        Storage::fake('public');

        $category = TrainingCategory::create(['name' => 'Test Category']);
        $program = TrainingProgram::create(['training_category_id' => $category->id, 'name' => 'Program', 'audience' => TrainingAudience::Staff->value]);
        $service = app(TrainingService::class);

        $material = $service->addMaterial($program, 'Handbook', TrainingMaterialType::Document, null, null, UploadedFile::fake()->create('v1.pdf', 100, 'application/pdf'), 0);
        $oldPath = $material->file_path;

        $updated = $service->updateMaterial($material, 'Handbook v2', TrainingMaterialType::Document, null, null, UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf'));

        $this->assertSame('Handbook v2', $updated->title);
        $this->assertNotSame($oldPath, $updated->file_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($updated->file_path);
    }

    public function test_switching_a_material_away_from_document_deletes_its_file()
    {
        Storage::fake('public');

        $category = TrainingCategory::create(['name' => 'Test Category']);
        $program = TrainingProgram::create(['training_category_id' => $category->id, 'name' => 'Program', 'audience' => TrainingAudience::Staff->value]);
        $service = app(TrainingService::class);

        $material = $service->addMaterial($program, 'Handbook', TrainingMaterialType::Document, null, null, UploadedFile::fake()->create('v1.pdf', 100, 'application/pdf'), 0);
        $oldPath = $material->file_path;

        $updated = $service->updateMaterial($material, 'Notes', TrainingMaterialType::Text, 'Now plain text.', null, null);

        $this->assertNull($updated->file_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_deleting_a_material_removes_its_file()
    {
        Storage::fake('public');

        $category = TrainingCategory::create(['name' => 'Test Category']);
        $program = TrainingProgram::create(['training_category_id' => $category->id, 'name' => 'Program', 'audience' => TrainingAudience::Staff->value]);
        $service = app(TrainingService::class);

        $material = $service->addMaterial($program, 'Handbook', TrainingMaterialType::Document, null, null, UploadedFile::fake()->create('v1.pdf', 100, 'application/pdf'), 0);
        $path = $material->file_path;

        $service->deleteMaterial($material);

        $this->assertModelMissing($material);
        Storage::disk('public')->assertMissing($path);
    }
}
