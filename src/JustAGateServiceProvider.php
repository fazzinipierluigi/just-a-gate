<?php

namespace Fazzinipierluigi\JustAGate;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class JustAGateServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->bootBladeDirectives();
        $this->registerMiddleware();

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    private function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware(config('acl.middleware', 'acl'), \Fazzinipierluigi\JustAGate\Middleware\AclCheck::class);
    }

    private function bootBladeDirectives(): void
    {
        Blade::if('can', function ($ability) {
            $user = Auth::user();
            return $user->can($ability);
        });
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/acl.php' => config_path('acl.php'),
        ], 'just_a_gate.config');

        // Registering package commands.
        $this->commands([
            \Fazzinipierluigi\JustAGate\Commands\PermissionInit::class,
            \Fazzinipierluigi\JustAGate\Commands\CreatePermission::class,
            \Fazzinipierluigi\JustAGate\Commands\AssignPermission::class,
            \Fazzinipierluigi\JustAGate\Commands\ImportPermission::class,
        ]);
    }
}
