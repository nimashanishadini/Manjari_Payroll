<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    protected $fillable = ['name', 'branch_id', 'department_id', 'brand', 'serial_number', 'ip_address', 'port', 'timezone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'port' => 'integer', 'last_sync_at' => 'datetime'];
    }

    public function stagingUsers(): HasMany
    {
        return $this->hasMany(BiometricStagingUser::class, 'device_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
