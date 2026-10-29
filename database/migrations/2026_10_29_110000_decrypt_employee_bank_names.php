<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->convert(encrypt: false);
    }

    public function down(): void
    {
        $this->convert(encrypt: true);
    }

    private function convert(bool $encrypt): void
    {
        DB::transaction(function () use ($encrypt) {
            DB::table('employees')->select('id', 'bank_name', 'bank_branch', 'bank_account_name')
                ->lockForUpdate()->chunkById(100, function ($employees) use ($encrypt) {
                    foreach ($employees as $employee) {
                        $values = [];
                        foreach (['bank_name', 'bank_branch', 'bank_account_name'] as $field) {
                            if ($employee->{$field} !== null) {
                                $values[$field] = $encrypt
                                    ? Crypt::encryptString($employee->{$field})
                                    : Crypt::decryptString($employee->{$field});
                            }
                        }
                        if ($values) DB::table('employees')->where('id', $employee->id)->update($values);
                    }
                });
        });
    }
};
