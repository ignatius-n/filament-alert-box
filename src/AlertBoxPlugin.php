<?php

namespace Agencetwogether\AlertBox;

use Agencetwogether\AlertBox\Filament\Pages\ManageAlertBox;
use Agencetwogether\AlertBox\Settings\SettingAlertBox;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;
use Throwable;

class AlertBoxPlugin implements Plugin
{
    use Concerns\CanCustomizeBuilder;
    use Concerns\CanCustomizeColors;
    use Concerns\CanCustomizePage;

    public function getId(): string
    {
        return 'agencetwogether/filament-alert-box';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                ManageAlertBox::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        $this->registerRenderHooks($panel);
    }

    protected function registerRenderHooks(Panel $panel): void
    {
        try {
            $alerts = app(SettingAlertBox::class)->alerts;
        } catch (Throwable $e) {
            return;
        }

        if (! filled($alerts)) {
            return;
        }

        $panelId = $panel->getId();

        foreach ($alerts as $alert) {
            $data = $alert['data'];
            $type = $alert['type'];

            FilamentView::registerRenderHook(
                name: $data['hook'],
                hook: function () use ($data, $panelId): string | View {
                    if (filament()->getCurrentPanel()?->getId() !== $panelId) {
                        return '';
                    }

                    return view('filament-alert-box::alert-box', ['preview' => false, 'config' => $data]);
                },
                scopes: AlertBox::getScopesPages($type, $data)
            );
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function tryGet(): ?static
    {
        try {
            /** @var static $plugin */
            $plugin = filament(app(static::class)->getId());

            return $plugin;
        } catch (Throwable) {
            return null;
        }
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
