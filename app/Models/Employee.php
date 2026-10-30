<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{

    use SoftDeletes;

    protected $hidden = ['documents', 'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number'];

    protected $fillable = [
        'blood_group',
        'profile_photo',
        'allow_self_service',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'branch_id',
        'department_id',
        'line_manager_id',
        'user_id',
        'employment_type',
        'probation_end_date',
        'confirmed_date',
        'name',
        'nic_number',
        'date_of_birth',
        'gender',
        'contact_number',
        'personal_email',
        'residential_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'branch_code',
        'joined_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'resigned_on' => 'date',
            'onboarding_completed_at' => 'datetime',
            'bank_account_number' => 'encrypted',
            'probation_end_date' => 'date',
            'confirmed_date' => 'date',
        ];
    }

    public function employeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'line_manager_id'
        );
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'line_manager_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employmentHistories(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class);
    }
}
