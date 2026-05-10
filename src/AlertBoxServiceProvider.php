<?php

namespace Agencetwogether\AlertBox;

use Agencetwogether\AlertBox\Commands\InstallCommand;
use Agencetwogether\AlertBox\Settings\SettingAlertBox;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;

class AlertBoxServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-alert-box';

    public static string $viewNamespace = 'filament-alert-box';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews(static::$viewNamespace)
            ->hasMigrations($this->getMigrations())
            ->hasCommands($this->getCommands());
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        $this->registerRenderHook();
    }

    public function registerRenderHook(): void
    {
        try {
            $alerts = app(SettingAlertBox::class)->alerts;
        } catch (Throwable $e) {
            return;
        }
        if (filled($alerts)) {
            foreach ($alerts as $alert) {
                $data = $alert['data'];
                $type = $alert['type'];

                FilamentView::registerRenderHook(
                    name: $data['hook'],
                    hook: fn (): View => view('filament-alert-box::alert-box', ['preview' => false, 'config' => $data]),
                    scopes: AlertBox::getScopesPages($type, $data)
                );
            }
        }
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            InstallCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_alert_box_settings',
        ];
    }
}
