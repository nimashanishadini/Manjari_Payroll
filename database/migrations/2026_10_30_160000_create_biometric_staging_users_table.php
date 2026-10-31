<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_staging_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('enrollment_id');
            $table->string('name');
            $table->string('source', 20)->default('Manual');
            $table->string('status', 30)->default('Pending HR Review')->index();
            $table->foreignId('promoted_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('ignore_reason')->nullable();
            $table->timestamps();
            $table->unique(['biometric_device_id', 'enrollment_id'], 'device_enrollment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_staging_users');
    }
};
