<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role', 20)->default('staff'));
        Schema::table('employees', function (Blueprint $table) {
            $table->string('blood_group', 3)->nullable();
            // Encrypted casts require text columns for the ciphertext.
            $table->text('bank_name')->nullable();
            $table->text('bank_branch')->nullable();
            $table->text('bank_account_name')->nullable();
            $table->text('bank_account_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn([
            'blood_group', 'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        ]));
    }
};
