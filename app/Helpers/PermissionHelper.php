<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    public static function hasAnyRole($roles)
    {
        if (!Auth::check()) {
            return false;
        }

        $roles = is_array($roles) ? $roles : explode('|', $roles);
        return Auth::user()->hasAnyRole($roles);
    }

    /**
     * Verificar si el usuario tiene todos los roles especificados
     */
    public static function hasAllRoles($roles)
    {
        if (!Auth::check()) {
            return false;
        }

        $roles = is_array($roles) ? $roles : explode('|', $roles);
        return Auth::user()->hasAllRoles($roles);
    }

    /**
     * Verificar si el usuario tiene alguno de los permisos especificados
     */
    public static function hasAnyPermission($permissions)
    {
        if (!Auth::check()) {
            return false;
        }

        $permissions = is_array($permissions) ? $permissions : explode('|', $permissions);
        return Auth::user()->hasAnyPermission($permissions);
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function can($permission)
    {
        if (!Auth::check()) {
            return false;
        }

        return Auth::user()->can($permission);
    }

    /**
     * Verificar si el usuario puede acceder a un módulo específico
     */
    public static function canAccessModule($module)
    {
        if (!Auth::check()) {
            return false;
        }

        $modulePermissions = [
            'managers' => ['super-admin', 'admin', 'manager'],
            'callcenters' => ['super-admin', 'admin', 'callcenter-manager', 'callcenter-agent'],
            'inventaries' => ['super-admin', 'admin', 'inventory-manager', 'inventory-staff'],
            'shops' => ['super-admin', 'admin', 'shop-manager', 'shop-staff'],
            'administratives' => ['super-admin', 'admin', 'administrative'],
            'returns' => ['super-admin', 'admin', 'manager', 'administrative', 'customer'],
        ];

        if (!isset($modulePermissions[$module])) {
            return false;
        }

        return Auth::user()->hasAnyRole($modulePermissions[$module]);
    }

    /**
     * Obtener el módulo principal al que tiene acceso el usuario
     */
    public static function getDefaultModule()
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if ($user->hasAnyRole(['super-admin', 'admin', 'manager'])) {
            return 'manager';
        }

        if ($user->hasAnyRole(['callcenter-manager', 'callcenter-agent'])) {
            return 'callcenter';
        }

        if ($user->hasAnyRole(['inventory-manager', 'inventory-staff'])) {
            return 'inventarie';
        }

        if ($user->hasAnyRole(['shop-manager', 'shop-staff'])) {
            return 'shop';
        }

        if ($user->hasRole('administrative')) {
            return 'administrative';
        }

        return 'home'; // Default para customers
    }

    /**
     * Verificar si el usuario puede realizar una acción específica en una devolución
     */
    public static function canManageReturn($return, $action = 'view')
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Super admin puede todo
        if ($user->hasRole('super-admin')) {
            return true;
        }

        switch ($action) {
            case 'view':
                return $user->canAccessReturn($return);

            case 'update':
                return $user->can('returns.update') && $user->canAccessReturn($return);

            case 'delete':
                return $user->can('returns.delete');

            case 'approve':
                return $user->can('returns.status.approve');

            case 'reject':
                return $user->can('returns.status.reject');

            case 'assign':
                return $user->can('returns.assign');

            default:
                return false;
        }
    }
}
