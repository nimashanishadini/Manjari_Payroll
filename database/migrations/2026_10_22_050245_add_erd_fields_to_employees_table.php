<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable();

            $table->string('nic_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();

            $table->string('contact_number')->nullable();
            $table->string('personal_email')->nullable();
            $table->text('residential_address')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            $table->string('branch_code')->nullable();
            $table->date('joined_date')->nullable();

            $table->string('status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'nic_number',
                'date_of_birth',
                'gender',
                'contact_number',
                'personal_email',
                'residential_address',
                'emergency_contact_name',
                'emergency_contact_phone',
                'branch_code',
                'joined_date',
                'status',
            ]);
        });
    }
};