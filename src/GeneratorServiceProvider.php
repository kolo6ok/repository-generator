<?php


namespace Kolo6ok\RepositoryGenerator;

use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/repository-generator.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('repository-generator.php');
        } else {
            $publishPath = base_path('config/repository-generator.php');
        }
        $this->publishes([$configPath => $publishPath], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/repository-generator.php';
        $this->mergeConfigFrom($configPath, 'repository-generator');

        $this->app->singleton(
            'command.repository-generator.generate',
            function ($app)  {
                return new GeneratorCommand($app['config'], $app['files']);
            }
        );

        $this->commands(
            'command.ide-helper.generate'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.repository-generator.generate');
    }
}
