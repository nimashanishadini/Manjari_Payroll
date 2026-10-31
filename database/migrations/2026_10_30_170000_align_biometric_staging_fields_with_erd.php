<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional: allow a partially applied migration to resume.
        foreach (['biometric_device_id' => 'device_id', 'enrollment_id' => 'raw_enrollment_id', 'name' => 'raw_name'] as $old => $new) {
            if (Schema::hasColumn('biometric_staging_users', $old)) {
                Schema::table('biometric_staging_users', fn (Blueprint $table) => $table->renameColumn($old, $new));
            }
        }
        Schema::table('biometric_staging_users', fn (Blueprint $table) => $table->string('raw_name')->nullable()->change());
        if (! Schema::hasColumn('biometric_staging_users', 'fetched_at')) {
            Schema::table('biometric_staging_users', fn (Blueprint $table) => $table->timestamp('fetched_at')->nullable());
        }
        // Old rows did not record a separate fetch time; creation time is the best available fallback.
        DB::table('biometric_staging_users')->whereNull('fetched_at')->update([
            'fetched_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
        ]);
        Schema::table('biometric_staging_users', fn (Blueprint $table) => $table->timestamp('fetched_at')->useCurrent()->nullable(false)->change());
    }

    public function down(): void
    {
        // Preserve unnamed records while restoring the old non-nullable column.
        DB::table('biometric_staging_users')->whereNull('raw_name')->update(['raw_name' => '']);
        Schema::table('biometric_staging_users', function (Blueprint $table) {
            $table->string('raw_name')->nullable(false)->change();
            $table->dropColumn('fetched_at');
        });
        Schema::table('biometric_staging_users', function (Blueprint $table) {
            $table->renameColumn('device_id', 'biometric_device_id');
            $table->renameColumn('raw_enrollment_id', 'enrollment_id');
            $table->renameColumn('raw_name', 'name');
        });
    }
};
