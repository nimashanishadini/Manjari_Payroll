<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemAlarm extends Model
{
    protected $fillable = ['employee_document_id', 'status', 'due_on', 'resolved_at'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'resolved_at' => 'datetime'];
    }

    public function reads(): HasMany
    {
        return $this->hasMany(SystemAlarmRead::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocument::class, 'employee_document_id');
    }
}
