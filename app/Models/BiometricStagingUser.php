<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricStagingUser extends Model
{
    public const PENDING = 'Pending HR Review';

    public const PROMOTED = 'Promoted';

    public const IGNORED = 'Ignored';

    protected $fillable = ['device_id', 'branch_id', 'raw_enrollment_id', 'raw_name', 'source', 'fetched_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'fetched_at' => 'datetime'];
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(BiometricStagingRead::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function promotedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'promoted_employee_id')->withTrashed();
    }
}
