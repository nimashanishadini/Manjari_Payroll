<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('employees')->whereNotNull('documents')->lockForUpdate()->chunkById(100, function ($employees) {
                foreach ($employees as $employee) {
                    $documents = json_decode($employee->documents, true, 512, JSON_THROW_ON_ERROR);
                    $changed = false;
                    foreach ($documents as &$category) {
                        foreach ($category['files'] as &$file) {
                            if (($file['disk'] ?? 'local') !== 'local') continue;
                            $source = Storage::disk('local');
                            $target = Storage::disk('employee_documents');
                            $path = $employee->id.'/'.basename($file['path']);
                            if (!$source->exists($file['path'])) {
                                throw new RuntimeException('An employee document is missing from private storage.');
                            }
                            // Keep the private original as a backup; only change the active reference.
                            $stream = $source->readStream($file['path']);
                            if (!is_resource($stream)) throw new RuntimeException('Unable to read employee document.');
                            try {
                                $target->put($path, $stream);
                            } finally {
                                if (is_resource($stream)) fclose($stream);
                            }
                            $file['disk'] = 'employee_documents';
                            $file['path'] = $path;
                            $changed = true;
                        }
                        unset($file);
                    }
                    unset($category);
                    if ($changed) DB::table('employees')->where('id', $employee->id)->update([
                        'documents' => json_encode($documents, JSON_THROW_ON_ERROR),
                    ]);
                }
            });
        });
    }

    public function down(): void
    {
        // Public references remain valid; original private files are preserved.
    }
};
