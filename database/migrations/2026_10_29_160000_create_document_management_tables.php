<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_masters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 150)->unique();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('requires_expiry')->default(false);
            $table->unsignedSmallInteger('reminder_days')->default(30);
            $table->timestamps();
        });
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_master_id')->constrained()->restrictOnDelete();
            $table->string('file_id')->unique();
            $table->string('disk');
            $table->text('path');
            $table->string('name');
            $table->date('expires_on')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('system_alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_document_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->date('due_on');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
        Schema::table('employees', fn (Blueprint $table) => $table->timestamp('onboarding_completed_at')->nullable());

        foreach (config('employee_documents') as $code => $name) {
            DB::table('document_type_masters')->insert(['code' => $code, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        $types = DB::table('document_type_masters')->pluck('id', 'code');
        // Keep the existing JSON and file paths intact; no files are moved or deleted.
        DB::table('employees')->whereNotNull('documents')->orderBy('id')->chunkById(100, function ($employees) use ($types) {
            foreach ($employees as $employee) {
                foreach (json_decode($employee->documents, true, 512, JSON_THROW_ON_ERROR) as $code => $category) {
                    if (! isset($types[$code])) {
                        continue;
                    }
                    foreach ($category['files'] ?? [] as $file) {
                        DB::table('employee_documents')->insert([
                            'employee_id' => $employee->id, 'document_type_master_id' => $types[$code],
                            'file_id' => $file['id'], 'disk' => $file['disk'] ?? 'local',
                            'path' => $file['path'], 'name' => $file['name'], 'expires_on' => $file['expires_on'] ?? null,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('onboarding_completed_at'));
        Schema::dropIfExists('system_alarms');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('document_type_masters');
    }
};
