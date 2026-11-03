<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('biometric_devices')->restrictOnDelete();
            $table->string('branch_code');
            $table->foreign('branch_code')->references('branch_code')->on('branches')->restrictOnDelete()->restrictOnUpdate();
            // Natural key: preserve the exact device enrollment ID, including leading zeros.
            $table->string('employee_id');
            $table->foreign('employee_id')->references('employee_id')->on('employees')->restrictOnDelete()->restrictOnUpdate();
            $table->dateTime('punch_time', 6);
            $table->string('verify_type')->nullable();
            $table->string('punch_state')->nullable();
            $table->string('state_source')->nullable();
            $table->boolean('is_anomalous')->default(false);
            $table->string('mask_status')->nullable();
            $table->string('source');
            // Request supertype is not implemented yet; reserved for Mobile/Correction.
            $table->unsignedBigInteger('source_request_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['device_id', 'employee_id', 'punch_time'], 'raw_attendance_device_punch_unique');
            $table->index(['employee_id', 'punch_time']);
            $table->index(['branch_code', 'punch_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_attendance');
    }
};
