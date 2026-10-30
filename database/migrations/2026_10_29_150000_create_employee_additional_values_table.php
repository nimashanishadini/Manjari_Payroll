<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_additional_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_additional_field_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->unique(['employee_id', 'employee_additional_field_id'], 'employee_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_additional_values');
    }
};
