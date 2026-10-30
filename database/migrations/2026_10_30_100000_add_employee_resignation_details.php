<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('resigned_on')->nullable()->index();
            $table->text('resignation_reason')->nullable();
            $table->foreignId('resigned_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resigned_by');
            $table->dropColumn(['resigned_on', 'resignation_reason']);
        });
    }
};
