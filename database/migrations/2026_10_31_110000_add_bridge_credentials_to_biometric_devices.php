<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->string('bridge_token_hash', 64)->nullable();
            $table->timestamp('bridge_token_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->dropColumn(['bridge_token_hash', 'bridge_token_expires_at']);
        });
    }
};
