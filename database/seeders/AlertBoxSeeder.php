<?php

namespace Agencetwogether\AlertBox\Database\Seeders;

use Agencetwogether\AlertBox\Settings\SettingAlertBox;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AlertBoxSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingAlertBox::class);

        $alerts = [
            [
                'data' => [
                    'hook' => 'panels::page.start',
                    'style' => 'info',
                    'title' => 'Need help ?',
                    'content' => '<p>You can contact us by phone/email</p>',
                    'showIcon' => true,
                ],
                'type' => 'global',
            ],
        ];

        $pageClass = $this->resolveFilamentPageClass();

        if ($pageClass !== null) {
            $alerts[] = [
                'data' => [
                    'hook' => 'panels::page.end',
                    'pages' => $this->resolveFilamentPageClass(),
                    'style' => 'tip',
                    'title' => 'Change your password',
                    'content' => '<p>For safety, don&#039;t forget to change your password after your first logging.</p>',
                    'showIcon' => true,
                ],
                'type' => 'page',
            ];
        }

        $existing = $settings->alerts;
        $settings->alerts = array_merge($existing, $alerts);
        $settings->save();
    }

    protected function resolveFilamentPageClass(string $keyword = 'Dashboard'): ?string
    {
        $directory = app_path('Filament/Pages');

        if (! File::isDirectory($directory)) {
            return 'Filament\\Pages\\Dashboard';
        }

        $files = File::files($directory);

        if (empty($files)) {
            return 'Filament\\Pages\\Dashboard';
        }

        $preferred = collect($files)->first(
            fn ($file) => str_contains($file->getFilenameWithoutExtension(), $keyword)
        );

        if (! $preferred) {
            return 'Filament\\Pages\\Dashboard';
        }

        return $this->extractFullyQualifiedClassName($preferred->getPathname());
    }

    protected function extractFullyQualifiedClassName(string $filePath): ?string
    {
        $content = File::get($filePath);

        preg_match('/^namespace\s+(.+?);/m', $content, $nsMatch);
        preg_match('/^class\s+(\w+)/m', $content, $classMatch);

        if (empty($nsMatch[1]) || empty($classMatch[1])) {
            return null;
        }

        return $nsMatch[1] . '\\' . $classMatch[1];
    }
}
