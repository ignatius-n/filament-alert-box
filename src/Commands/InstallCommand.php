<?php

namespace Agencetwogether\AlertBox\Commands;

use Agencetwogether\AlertBox\Commands\Concerns\CanRegisterPlugin;
use Agencetwogether\AlertBox\Commands\Concerns\DiscoversPanelProviders;
use Agencetwogether\AlertBox\Commands\Concerns\ManagesThemeStyles;
use Agencetwogether\AlertBox\Database\Seeders\AlertBoxSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\select;

class InstallCommand extends Command
{
    use CanRegisterPlugin;
    use DiscoversPanelProviders;
    use ManagesThemeStyles;

    protected ?string $panelId = null;

    protected bool $shieldConfigured = false;

    public $signature = 'filament-alert-box:install
                        {--panel= : Panel ID to register the plugin in}
                        {--seed : Seed alerts demo}
                        {--force : Overwrite existing config file}';

    public $description = 'Install the Alert Box plugin.';

    public function handle(): int
    {
        $this->info('Installing AlertBox plugin...');
        $this->newLine();

        if ($this->confirm('Publish configuration file?', false)) {
            $this->comment('Publishing configuration...');
            $this->callSilently('vendor:publish', [
                '--tag' => 'filament-alert-box-config',
                '--force' => $this->option('force'),
            ]);
            $this->info('  Config published to config/filament-alert-box.php');
        }

        $this->ensureSettingsTableExists();

        $this->comment('Publishing migrations...');
        $this->callSilently('vendor:publish', [
            '--tag' => 'filament-alert-box-migrations',
        ]);
        $this->info('  Migrations published');

        if ($this->confirm('Run migrations now?', true)) {
            $this->comment('Running migrations...');
            $this->call('migrate');
            $this->info('  Migrations complete');
        }

        if ($this->option('seed') || $this->confirm('Seed alerts demo?', true)) {
            $this->comment('Seeding alerts demo...');
            $this->call('db:seed', [
                '--class' => AlertBoxSeeder::class,
            ]);
            $this->info('  Alerts demo seeded');
        }

        if ($this->confirm('Publish translation files for customization?')) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'filament-alert-box-translations',
            ]);
            $this->info('  Translations published to lang/vendor/filament-alert-box/');
        }

        if ($this->confirm('Publish views for customization?')) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'filament-alert-box-views',
            ]);
            $this->info('  Views published to resources/views/vendor/filament-alert-box/');
        }

        $this->registerInPanel();
        $this->registerThemeStylesForPanel();
        $this->generateShieldPermissions();

        $this->newLine();
        $this->info('AlertBox plugin installed successfully!');

        return self::SUCCESS;
    }

    protected function ensureSettingsTableExists(): void
    {
        if (Schema::hasTable(config('settings.repositories.database.table') ?? 'settings')) {
            return;
        }

        $this->comment('Publishing spatie/laravel-settings migration...');
        $this->callSilently('vendor:publish', [
            '--provider' => 'Spatie\LaravelSettings\LaravelSettingsServiceProvider',
            '--tag' => 'migrations',
        ]);
        $this->info('  Settings migration published');

        $this->comment('Running settings migration...');
        $this->call('migrate');
        $this->info('  Settings table created');
    }

    protected function registerInPanel(): void
    {
        $panelProviders = $this->discoverPanelProviders();

        if (empty($panelProviders)) {
            $this->components->warn('No panel providers found in app/Providers/Filament/. Register AlerBoxPlugin::make() manually.');

            return;
        }

        $this->panelId = $this->option('panel');

        if ($this->panelId === null) {
            $this->panelId = select(
                label: 'Which panel should AlertBox be registered in?',
                options: array_keys($panelProviders),
                required: true,
            );
        }

        if (! isset($panelProviders[$this->panelId])) {
            $this->components->error("Panel provider not found for: {$this->panelId}");

            return;
        }

        $this->comment("Registering AlertBoxPlugin in {$this->panelId} panel...");
        $this->registerPlugin($panelProviders[$this->panelId]);
    }

    protected function registerThemeStylesForPanel(): void
    {
        if ($this->panelId === null) {
            return;
        }

        $cssPath = $this->resolveThemeCssPath($this->panelId);

        if ($cssPath === null) {
            return;
        }

        if ($this->confirm('Register AlertBox styles in your custom Filament theme?', true)) {
            $this->comment('Registering AlertBox styles...');
            $this->registerThemeStyles($this->panelId);
        }
    }

    protected function generateShieldPermissions(): void
    {
        $configPath = config_path('filament-shield.php');

        if (! file_exists($configPath)) {
            return;
        }

        if (! $this->confirm('Generate Shield permission for AlertBox page?', true)) {
            return;
        }

        $this->comment('Generating Shield permission for AlertBox page...');

        $args = [
            PHP_BINARY, 'artisan', 'shield:generate',
            '--page=ManageAlertBox',
            '--option=permissions',
            '--no-interaction',
        ];

        if ($this->panelId !== null) {
            $args[] = "--panel={$this->panelId}";
        }

        $process = new Process($args, base_path());
        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful()) {
            $this->shieldConfigured = true;
            $this->info('  Shield permission generated');
        } else {
            $this->components->warn('Could not generate Shield permissions automatically. Run manually:');
            $panelFlag = $this->panelId !== null ? " --panel={$this->panelId}" : '';
            $this->line("  php artisan shield:generate{$panelFlag} --option=permissions");
        }
    }
}
