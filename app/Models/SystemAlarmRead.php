<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAlarmRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'system_alarm_id', 'status', 'due_on', 'read_at'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'read_at' => 'datetime'];
    }
}
