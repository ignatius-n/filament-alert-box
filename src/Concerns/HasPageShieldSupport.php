<?php

namespace Agencetwogether\AlertBox\Concerns;

use Agencetwogether\AlertBox\AlertBoxPlugin;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Facades\Filament;

trait HasPageShieldSupport
{
    protected static ?string $pagePermissionKey = null;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }

    /**
     * Access control priority chain:
     *
     * 1. filament-shield is installed AND a permission exists for this page
     *
     * 2. The plugin author explicitly called ->authorize(fn () => ...)
     *
     * 3. No shield, no explicit ->authorize() the page is shown to everyone with panel access)
     */
    public static function canAccess(): bool
    {
        // --- Priority 1 : filament-shield ---
        if (static::isShieldAvailable()) {
            $permission = static::getPagePermission();
            $user = Filament::auth()->user();

            if ($permission && $user) {
                return $user->can($permission);
            }
        }

        // --- Priority 2 : explicit ->authorize() on the plugin ---
        $plugin = AlertBoxPlugin::tryGet();

        if ($plugin?->isAuthorizeExplicitlyConfigured()) {
            return $plugin->isAuthorized();
        }

        // --- Priority 3 : default — allow access ---
        return true;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    protected static function isShieldAvailable(): bool
    {
        return class_exists(FilamentShieldPlugin::class);
    }

    protected static function getPagePermission(): ?string
    {
        if (static::$pagePermissionKey === null) {
            try {
                $page = FilamentShield::getPages()[static::class] ?? null;
                static::$pagePermissionKey = $page
                    ? (array_key_first($page['permissions']) ?? '')
                    : '';
            } catch (\Throwable) {
                static::$pagePermissionKey = '';
            }
        }

        return static::$pagePermissionKey ?: null;
    }
}
