<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAdditionalField extends Model
{
    protected $fillable = ['name', 'type', 'visibility', 'show_in_person_list', 'options'];

    protected function casts(): array
    {
        return ['show_in_person_list' => 'boolean', 'options' => 'array'];
    }
}
