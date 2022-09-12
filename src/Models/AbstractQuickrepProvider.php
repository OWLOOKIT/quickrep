<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 6/20/18
 * Time: 12:01 PM
 */

namespace Owlookit\Quickrep\Models;

use Owlookit\Quickrep\Console\AbstractQuickrepInstallCommand;
use Illuminate\Support\ServiceProvider;

abstract class AbstractQuickrepProvider extends ServiceProvider
{
    abstract protected function onBeforeRegister();

    public function register()
    {
        $this->onBeforeRegister();
    }

    /**
     * @param $class
     * @throws \Exception
     *
     * Given an AbstractQuickrepInstallCommand class, check to see if the required
     * views are published to the resources directory. If they don't exist, throw
     * an exception
     */
    public static function ensureViewsExist($class)
    {
        // Only check this if we are running in the web
        if (php_sapi_name() !== 'cli') {

            // Make sure our class is a subclass of AbstractQuickrepInstallCommand
            if (in_array(AbstractQuickrepInstallCommand::class, class_parents($class))) {

                // Loop through required views, and if one doesn't exist in the app's resources directory, throw exception
                foreach ($class::$views as $view) {
                    $publishedViewPath = resource_path('views') . DIRECTORY_SEPARATOR . $view;
                    if (!file_exists($publishedViewPath)) {
                        throw new \Exception("You are missing view `$view` in your resources directory. You may need to run `php artisan quickrep:install` at the root of your project");
                    }
                }
            }
        }
    }
}
