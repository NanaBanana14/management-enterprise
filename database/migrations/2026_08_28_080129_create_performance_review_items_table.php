<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['performance_review_id', 'kpi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_review_items');
    }
};
