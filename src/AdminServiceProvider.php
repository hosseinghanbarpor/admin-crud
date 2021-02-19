<?php

namespace Samavin\LaravelAdmin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Samavin\LaravelAdmin\Commands\CrudMakeCommand;
use Samavin\LaravelAdmin\Commands\InstallCommand;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../files/.php_cs.dist' => base_path('.php_cs.dist'),
            ], 'phpcs');

            $this->commands([
                InstallCommand::class,
                CrudMakeCommand::class,
            ]);
        }

        Builder::macro('exportOrPaginate', function () {
            if (request()->get('perPage')) {
                return $this
                    ->paginate(request()->get('perPage'))
                    ->appends(request()->query());
            }

            return $this->get();
        });
    }

}
