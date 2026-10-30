<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_alarm_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('system_alarm_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->date('due_on');
            $table->timestamp('read_at');
            $table->unique(['user_id', 'system_alarm_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_alarm_reads');
    }
};
