<?php

namespace HolartWeb\AxoraCMS\Models;

use HolartWeb\AxoraCMS\Enums\AdminRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TAdministrator extends Authenticatable
{
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_administrators';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'is_active' => 'boolean',
            'role' => AdminRole::class,
        ];
    }

    /**
     * Check if admin has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    /**
     * Check if admin can assign role
     */
    public function canAssignRole(AdminRole $role): bool
    {
        return $this->role->canAssignRole($role);
    }

    /**
     * Check if admin is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SUPER_ADMIN;
    }

    /**
     * Check if admin is administrator
     */
    public function isAdministrator(): bool
    {
        return $this->role === AdminRole::ADMINISTRATOR;
    }

    /**
     * Check if admin is manager
     */
    public function isManager(): bool
    {
        return $this->role === AdminRole::MANAGER;
    }
}
