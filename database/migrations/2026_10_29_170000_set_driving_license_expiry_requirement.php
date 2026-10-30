<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_type_masters')->where('code', 'driving_license')->update(['requires_expiry' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('document_type_masters')->where('code', 'driving_license')->update(['requires_expiry' => false, 'updated_at' => now()]);
    }
};
