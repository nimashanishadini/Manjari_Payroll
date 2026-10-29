<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class EmploymentHistory extends Model
{

protected $table = 'employment_history';
    protected $fillable = [
    'employee_id',
    'department_id',
    'designation_id',
    'manager_id',
    'start_date',
    'end_date',
    'change_reason',
];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'manager_id'
        );
    }
}