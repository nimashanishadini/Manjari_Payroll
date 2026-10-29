<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('employees')->whereNotNull('profile_photo')->lockForUpdate()->chunkById(100, function ($employees) {
                foreach ($employees as $employee) {
                    $path = basename($employee->profile_photo);
                    $source = Storage::disk('local');
                    $target = Storage::disk('employee_photos');
                    if ($source->exists($employee->profile_photo)) {
                        $stream = $source->readStream($employee->profile_photo);
                        if (!is_resource($stream)) throw new RuntimeException('Unable to read employee photo.');
                        try {
                            $target->put($path, $stream);
                        } finally {
                            if (is_resource($stream)) fclose($stream);
                        }
                    } elseif (!$target->exists($path)) {
                        throw new RuntimeException('An employee photo is missing from storage.');
                    }
                    DB::table('employees')->where('id', $employee->id)->update(['profile_photo' => $path]);
                }
            });
        });
    }

    public function down(): void
    {
        // Keep public references valid and preserve original private files as backups.
    }
};
