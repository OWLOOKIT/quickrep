<?php

namespace Owlookit\Quickrep\Console;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Owlookit\Quickrep\Models\QuickrepDatabase;
use phpDocumentor\Reflection\Types\Static_;

class QuickrepInstallCommand extends AbstractQuickrepInstallCommand
{
    const CONFIG_MIGRATIONS_PATH = 'vendor/owlookit/quickrep/database/migrations';
    /**
     * The views that need to be exported.
     *
     * @var array
     */
    public static $views = [
        // Multiple views use the shared menu
        'quickrep/menu.blade.php',
        // SQL Pretty-Printing views
        'quickrep/sql.blade.php',
        'quickrep/layouts/sql_layout.blade.php',
        // Card views
        'quickrep/card.blade.php',
        'quickrep/layouts/card_layout.blade.php',
        // Graph views
        'quickrep/d3graph.blade.php',
        'quickrep/layouts/d3graph_layout.blade.php',
        // Tabular views
        'quickrep/tabular.blade.php',
        'quickrep/layouts/tabular_layout.blade.php',
        // Tree-card views
        'quickrep/tree_card.blade.php',
        'quickrep/layouts/tree_card_layout.blade.php',
    ];
    /**
     * Base directory indicating where the $views are located
     *
     * @var string
     */
    protected static $view_path = __DIR__ . '/../../views';
    /**
     * Map of assets (CSS and Javascript) that need to be exported to public,
     * in the format 'source => target'
     *
     * @var string[]
     */
    protected static $assets = [
        // Core assets brought into vendor by composer
        // Bootstrap bundle contains Popper.js so we don't need to add it
        '/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js' => '/core/bootstrap/bootstrap.bundle.min.js',
        '/vendor/twbs/bootstrap/dist/css/bootstrap.min.css' => '/core/bootstrap/bootstrap.min.css',
        '/vendor/components/jquery/jquery.min.js' => '/core/js/jquery.min.js',
        '/vendor/moment/moment/min/moment.min.js' => '/core/js/moment.min.js',
        '/vendor/fortawesome/font-awesome/webfonts' => '/core/font-awesome/webfonts',
        '/vendor/fortawesome/font-awesome/css/all.min.css' => '/core/font-awesome/css/all.min.css',
        '/vendor/fortawesome/font-awesome/js/all.min.js' => '/core/font-awesome/js/all.min.js',

        // Core assets that live in the quickrep repo
        '/vendor/owlookit/quickrep/assets/core/css' => '/core/css',
        '/vendor/owlookit/quickrep/assets/core/js' => '/core/js',

        // Graph Assets
        '/vendor/owlookit/quickrep/assets/quickrepbladegraph/css' => '/quickrepbladegraph/css',
        '/vendor/owlookit/quickrep/assets/quickrepbladegraph/js' => '/quickrepbladegraph/js',

        // Tabular Assets
        '/vendor/owlookit/quickrep/assets/quickrepbladetabular/datatables' => '/quickrepbladetabular/datatables',
        '/vendor/owlookit/quickrep/assets/quickrepbladetabular/js' => '/quickrepbladetabular/js',
    ];
    protected static $config_file = __DIR__ . '/../../config/quickrep.php';
    /**
     * @var string
     *
     * Console command signature
     */
    protected $signature = 'quickrep:install
                    {--database= : Deprecated. Kept for backward compatibility}
                    {--force : Overwrite published assets/config when supported}
                    {--config-only : Only run Quickrep config database migrations}
                    {--skip-database : Skip Quickrep database migrations}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all available Quickrep packages';

    public function handle()
    {
        Config::set('quickrep:install_api.running', true);

        if (! $this->option('config-only')) {
            $this->info("Creating directories....");
            $this->createDirectories();
            $this->info("Done.");

            $this->info("Exporting views....");
            $this->exportViews();
            $this->info("Done.");

            $this->info("exporting config....");
            if (!empty(static::$config_file)) {
                $this->exportConfig();
            }
            $this->info("Done.");

            $this->info("exporting assets....");
            $this->exportAssets();
            $this->info("Done.");

            if ($this->config_changes) {
                $path_parts = pathinfo(self::config_file);
                $user_config_file = $path_parts['basename'];
                $config_namespace = $path_parts['filename'];
                $array = Config::get($config_namespace);
                $data = var_export($array, 1);

                if (File::put(config_path($user_config_file), "<?php\n return $data;")) {
                    $this->info("Wrote new config file");
                } else {
                    $this->error("There were config changes, but there was an error writing config file.");
                }
            }
        }

        if (! $this->option('skip-database')) {
            $this->info("Installing Quickrep Database");
            $this->installDatabase();
        }

        $this->info("Installation Successful.");

        return self::SUCCESS;
    }

    protected function installDatabase(): bool
    {
        $this->info('Running Quickrep config migrations...');

        $configConnection = quickrep_config_db();

        if (empty($configConnection)) {
            $this->error('Quickrep config connection is not configured.');

            return false;
        }

        $this->info(sprintf(
            'Using Quickrep config connection [%s].',
            $configConnection
        ));

        $this->migrateDatabase($configConnection, self::CONFIG_MIGRATIONS_PATH);

        $this->info('Done.');

        return true;
    }

    /**
     * @deprecated TopIQ uses Laravel connections and does not create/drop Quickrep databases.
     */
    public function runQuickrepInitialCacheMigration($quickrep_cache_db_name)
    {
        throw new \LogicException('Legacy Quickrep cache database installation is disabled.');
    }

    /**
     * @deprecated TopIQ uses Laravel connections and does not create/drop Quickrep databases.
     */
    public function runQuickrepInitialConfigMigration($quickrep_config_db_name)
    {
        throw new \LogicException('Legacy Quickrep config database installation is disabled.');
    }

    public function migrateDatabase($connectionName, $path): void
    {
        $exitCode = Artisan::call('migrate', [
            '--force' => true,
            '--database' => $connectionName,
            '--path' => $path,
        ], $this->getOutput());

        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf(
                'Quickrep migrations failed for connection [%s] and path [%s].',
                $connectionName,
                $path
            ));
        }
    }
}
