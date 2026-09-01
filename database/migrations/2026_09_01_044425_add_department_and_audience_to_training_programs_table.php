<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('training_category_id')->constrained()->restrictOnDelete();
            $table->string('audience')->default('staff')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('audience');
        });
    }
};
