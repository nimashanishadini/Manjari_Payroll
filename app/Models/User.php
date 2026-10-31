<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public function canReviewBiometricEnrollments(): bool
    {
        return in_array($this->role, ['hr', 'admin'], true);
    }

    public function canManageBiometricDevices(): bool
    {
        return in_array($this->role, ['hr', 'admin'], true);
    }

    public function canManageEmploymentStructure(): bool
    {
        return in_array($this->role, ['hr', 'admin'], true);
    }

    public function canManageEmployeeDocuments(): bool
    {
        return in_array($this->role, ['hr', 'admin'], true);
    }

    public function canManageEmployeeBankDetails(): bool
    {
        return in_array($this->role, ['hr', 'admin'], true);
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
