<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricStagingRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'biometric_staging_user_id', 'read_at'];
}
