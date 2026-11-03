<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawAttendance extends Model
{
    protected $table = 'raw_attendance';
    public $timestamps = false;
    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['punch_time' => 'immutable_datetime', 'created_at' => 'immutable_datetime', 'is_anomalous' => 'boolean'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id')->withTrashed();
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'branch_code');
    }
}
