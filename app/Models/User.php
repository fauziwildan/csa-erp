<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'user_store')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryStore()
    {
        return $this->belongsToMany(Store::class, 'user_store')
            ->wherePivot('is_primary', true)
            ->first();
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse');
    }

    public function primaryWarehouse()
    {
        return $this->warehouses()->first();
    }

    public function hasGlobalFinanceAccess(): bool
    {
        return $this->hasAnyRole(['superadmin', 'owner', 'finance']);
    }
}
