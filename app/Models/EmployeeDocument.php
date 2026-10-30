<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeDocument extends Model
{
    protected $fillable = ['employee_id', 'document_type_master_id', 'file_id', 'disk', 'path', 'name', 'expires_on'];

    protected $hidden = ['disk', 'path'];

    protected function casts(): array
    {
        return ['expires_on' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentTypeMaster::class, 'document_type_master_id');
    }

    public function alarm(): HasOne
    {
        return $this->hasOne(SystemAlarm::class);
    }
}
