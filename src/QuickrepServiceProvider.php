<?php

namespace Owlookit\Quickrep;

use Owlookit\Quickrep\Console\MakeGraphReportCommand;
use Owlookit\Quickrep\Console\MakeTabularReportCommand;
use Owlookit\Quickrep\Console\QuickrepDebugCommand;
use Owlookit\Quickrep\Console\QuickrepInstallCommand;
use Owlookit\Quickrep\Console\MakeCardsReportCommand;
use Owlookit\Quickrep\Console\QuickrepReportCheckCommand;
use Owlookit\Quickrep\Models\QuickrepDatabase;
use Owlookit\Quickrep\Services\SocketService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Class QuickrepServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /*
     * Registration happens before boot, so this is where we gather static configuration
     * and register things to be used later.
     */
	public function register()
	{
        require_once __DIR__ . '/helpers.php';

        /*
         * Register our quickrep view make command which:
         *  - Copies views
         *  - Exports configuration
         *  - Exports Assets
         */
        $this->commands([
            QuickrepInstallCommand::class,
            QuickrepDebugCommand::class,
	    	QuickrepReportCheckCommand::class,
            MakeTabularReportCommand::class,
            MakeCardsReportCommand::class,
            MakeGraphReportCommand::class
        ]);

        /*
         * Merge with main config so parameters are accessable.
         * Try to load config from the app's config directory first,
         * then load from the package.
         */
        if ( file_exists(  config_path( 'quickrep.php' ) ) ) {
            $this->mergeConfigFrom(
                config_path( 'quickrep.php' ), 'quickrep'
            );
        } else {
            $this->mergeConfigFrom(
                __DIR__.'/../config/quickrep.php', 'quickrep'
            );
        }
	}//end register function..


	public $is_socket_ok = false; //start assuming it is not.
	public $is_socket_checked = false;
    /**
     * @param Router $router
     *
     * This function is called after all providers have been registered,
     * and the database hass been set up.
     */
    public function boot( Router $router )
    {
        if (php_sapi_name() !== 'cli') {
            // Register the cache database connection if we have a quickrep db,
            // but only if we're running a web route, not during install commands
            $quickrep_cache_db = quickrep_cache_db();
            if (QuickrepDatabase::doesDatabaseExist($quickrep_cache_db)) {
                QuickrepDatabase::configure($quickrep_cache_db);
            }

            // Register and configure the config DB
            $quickrep_config_db = quickrep_config_db();
            if ( QuickrepDatabase::doesDatabaseExist( $quickrep_config_db ) ) {
                QuickrepDatabase::configure( $quickrep_config_db );
            }

            // Validate that there is only one is_default_socket for a wrench, throw an exception
            // if there is a wrench with Zero default sockets, or a wrench with more than one
            // default socket, as this can result unexpected behavior
            if (!$this->is_socket_checked) {
                $this->is_socket_ok = SocketService::checkIsDefaultSocket();
                $this->is_socket_checked = true;
            }
        }

        // routes

        // Boot our reports, but only in web mode. We don't care to register reports
        // during composer package discovery, or installation
        if (php_sapi_name() !== 'cli') {
            $this->registerApiRoutes();
            $this->registerWebRoutes();
            $this->registerReports();
            $this->loadViewsFrom( resource_path( 'views/quickrep' ), 'Quickrep');
        }
    }

    /**
     * Register the application's Quickrep reports.
     *
     * @return void
     */
    protected function registerReports()
    {
        $reportDir = report_path();
        if ( File::isDirectory($reportDir) ) {
            Quickrep::reportsIn( $reportDir );
        }
    }

    /**
     * Load the given routes file if routes are not already cached.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadRoutesFrom($path)
    {
        if (! $this->app->routesAreCached()) {
            require $path;
        }
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerApiRoutes()
    {
        Route::group( $this->routeConfiguration(), function () {

            // Load the core quickrep api routes including sockets
            $this->loadRoutesFrom(__DIR__.'/../routes/api.sockets.php');

            $tabular_api_prefix = config('quickrep.TABULAR_API_PREFIX','Quickrep');
            Route::group( ['prefix' => $tabular_api_prefix ], function() {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.tabular.php');
            });

            $graph_api_prefix = config('quickrep.GRAPH_API_PREFIX','QuickrepGraph');
            Route::group( ['prefix' => $graph_api_prefix ], function() {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.graph.php');
            });

            $tree_api_prefix = config('quickrep.TREE_API_PREFIX','QuickrepTree');
            Route::group( ['prefix' => $tree_api_prefix ], function() {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.tree.php');
            });
        });
    }

    /**
     * Get the WEB route configurations
     */
    protected function registerWebRoutes()
    {
        Route::group($this->webRouteConfiguration(), function() {

            // Load the pretty-print SQL routes from web.sql.php using the configured prefix
            $sql_print_prefix = config( 'quickrep.SQL_PRINT_PREFIX','QuickrepSQL' );
            Route::group([ 'prefix' => $sql_print_prefix ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.sql.php');
            });

            Route::group([ 'prefix' => config('quickrep.CARD_URI_PREFIX', 'QuickrepCard') ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.card.php');
            });

            Route::group([ 'prefix' => config('quickrep.GRAPH_URI_PREFIX', 'QuickrepGraph') ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.graph.php');
            });

            Route::group([ 'prefix' => config('quickrep.TABULAR_URI_PREFIX', 'Quickrep') ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.tabular.php');
            });

            Route::group([ 'prefix' => config('quickrep.TREECARD_URI_PREFIX', 'QuickrepTreeCard') ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.tree_card.php');
            });
        });
    }

    /**
     * Get the API route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration()
    {
        $middleware = config('quickrep.MIDDLEWARE',[ 'api' ]);

        return [
            'namespace' => 'Owlookit\Quickrep\Http\Controllers',
            'domain' => config('quickrep.domain', null),
            'as' => 'quickrep.api.',
            'prefix' => config( 'quickrep.API_PREFIX', 'zapi' ),
            'middleware' => $middleware,
        ];
    }

    /**
     * Get the web route group configuration array.
     *
     * @return array
     */
    protected function webRouteConfiguration()
    {
        $middleware = config('quickrep.WEB_MIDDLEWARE',[ 'web' ]);

        return [
            'namespace' => 'Owlookit\Quickrep\Http\Controllers',
            //  'domain' => config('quickrep.domain', null),
            'as' => 'quickrep.web.',
            'prefix' => '',
            'middleware' => $middleware,
        ];
    }
}
