<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTypeMaster extends Model
{
    protected $fillable = ['code', 'name', 'is_mandatory', 'requires_expiry', 'reminder_days'];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean', 'requires_expiry' => 'boolean', 'reminder_days' => 'integer'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }
}
