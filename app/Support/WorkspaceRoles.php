<?php

namespace App\Support;

class WorkspaceRoles
{
    public const SESSION_KEY = 'workspace_role';

    public const ROLES = [
        'admin' => [
            'label' => 'Admin',
            'description' => 'Dashboard, menu, staff & reports',
            'route' => 'admin.dashboard',
            'icon' => 'admin',
        ],
        'waiter' => [
            'label' => 'Waiter',
            'description' => 'Floor plan & orders',
            'route' => 'waiter.floor',
            'icon' => 'waiter',
        ],
        'kitchen' => [
            'label' => 'Kitchen',
            'description' => 'Kitchen display system',
            'route' => 'kitchen.kds',
            'icon' => 'kitchen',
        ],
        'cashier' => [
            'label' => 'Cashier',
            'description' => 'Payments & receipts',
            'route' => 'cashier.terminal',
            'icon' => 'cashier',
        ],
        'host' => [
            'label' => 'Host',
            'description' => 'Reservations',
            'route' => 'host.reservations',
            'icon' => 'host',
        ],
    ];

    public static function all(): array
    {
        return self::ROLES;
    }

    public static function isValid(string $role): bool
    {
        return array_key_exists($role, self::ROLES);
    }

    public static function route(string $role): string
    {
        return route(self::ROLES[$role]['route']);
    }

    public static function label(string $role): string
    {
        return self::ROLES[$role]['label'] ?? ucfirst($role);
    }

    public static function current(): ?string
    {
        $role = session(self::SESSION_KEY);

        return is_string($role) && self::isValid($role) ? $role : null;
    }

    public static function set(string $role): void
    {
        session([self::SESSION_KEY => $role]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
