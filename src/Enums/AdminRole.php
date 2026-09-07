<?php

namespace HolartWeb\AxoraCMS\Enums;

enum AdminRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMINISTRATOR = 'administrator';
    case MANAGER = 'manager';

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role label
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Супер администратор',
            self::ADMINISTRATOR => 'Администратор',
            self::MANAGER => 'Менеджер',
        };
    }

    /**
     * Get role permissions
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'view_dashboard',
                'manage_administrators',
                'view_logs',
                'manage_orders',
                'manage_catalogs',
                'manage_products',
                'assign_any_role',
            ],
            self::ADMINISTRATOR => [
                'view_dashboard',
                'view_logs_limited',
                'manage_orders',
                'manage_catalogs',
                'manage_products',
                'assign_manager_role',
            ],
            self::MANAGER => [
                'view_dashboard',
                'view_orders',
                'edit_orders',
                'view_catalogs',
                'view_products',
                'edit_products',
            ],
        };
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Check if role can assign another role
     */
    public function canAssignRole(AdminRole $role): bool
    {
        return match ($this) {
            self::SUPER_ADMIN => true,
            self::ADMINISTRATOR => $role !== self::SUPER_ADMIN,
            self::MANAGER => false,
        };
    }
}
