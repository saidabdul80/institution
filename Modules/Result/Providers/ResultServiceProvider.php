<?php

namespace Modules\Result\Providers;

use Illuminate\Support\ServiceProvider;

class ResultServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Result';
    protected $moduleNameLower = 'result';

    public function boot()
    {
        $this->registerTranslations();
        // Config registration disabled - not needed for basic functionality
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // Register service bindings
        $this->app->bind(
            \Modules\Result\Repositories\ResultRepository::class,
            \Modules\Result\Repositories\ResultRepository::class
        );

        $this->app->bind(
            \Modules\Result\Services\ResultService::class,
            \Modules\Result\Services\ResultService::class
        );

        $this->app->bind(
            \Modules\Result\Services\ResultComputationService::class,
            \Modules\Result\Services\ResultComputationService::class
        );
    }



    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
