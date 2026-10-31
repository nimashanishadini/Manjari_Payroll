<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'branch_code',
        'name',
        'timezone',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function biometricDevices(): HasMany
    {
        return $this->hasMany(BiometricDevice::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
