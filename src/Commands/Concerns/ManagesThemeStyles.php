<?php

namespace Agencetwogether\AlertBox\Commands\Concerns;

trait ManagesThemeStyles
{
    private const THEME_SOURCE_LINE = "@source '../../../../vendor/agencetwogether/filament-alert-box/resources/views/**/*';";

    protected function registerThemeStyles(string $panelId): void
    {
        $cssPath = $this->resolveThemeCssPath($panelId);

        if ($cssPath === null) {
            return;
        }

        $content = file_get_contents($cssPath);

        if ($content === false) {
            $this->components->warn("Could not read theme CSS file: {$cssPath}");

            return;
        }

        if (str_contains($content, 'agencetwogether/filament-alert-box')) {
            $this->components->warn('AlertBox styles are already registered in the theme CSS.');

            return;
        }

        $content = rtrim($content) . "\n" . self::THEME_SOURCE_LINE . "\n";

        file_put_contents($cssPath, $content);

        $relativePath = str_replace(base_path() . '/', '', $cssPath);
        $this->info("  AlertBox styles registered in {$relativePath}");
    }

    protected function resolveThemeCssPath(string $panelId): ?string
    {
        $cssPath = resource_path("css/filament/{$panelId}/theme.css");

        if (! file_exists($cssPath)) {
            return null;
        }

        return $cssPath;
    }
}
