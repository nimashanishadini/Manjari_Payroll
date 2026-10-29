<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Employee identification
            $table->string('employee_id')->unique();

            // Employee details
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();

            // Organization details
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();

            // Reporting manager
            $table->foreignId('line_manager_id')
                ->nullable()
                ->constrained('employees')
                ->restrictOnDelete();

            // Login account (optional)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Employment details
            $table->enum('employment_type', [
                'Probation',
                'Permanent',
                'Contract',
                'Intern',
            ]);

            $table->date('probation_end_date')->nullable();
            $table->date('confirmed_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};