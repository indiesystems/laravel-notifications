<?php

namespace IndieSystems\Notifications;

use Illuminate\Support\ServiceProvider;
use IndieSystems\Notifications\Console\InstallCommand;
use IndieSystems\Notifications\Console\PruneCommand;

class IndieNotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/indie-notifications.php', 'indie-notifications');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'indie-notifications');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'indie-notifications');

        $this->commands([
            InstallCommand::class,
            PruneCommand::class,
        ]);

        // Config
        $this->publishes([
            __DIR__.'/../config/indie-notifications.php' => config_path('indie-notifications.php'),
        ], 'indie-notifications-config');

        // Views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/indie-notifications'),
        ], 'indie-notifications-views');

        // Translations
        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/indie-notifications'),
        ], 'indie-notifications-lang');

        // Publish everything
        $this->publishes([
            __DIR__.'/../config/indie-notifications.php' => config_path('indie-notifications.php'),
            __DIR__.'/../resources/views' => resource_path('views/vendor/indie-notifications'),
            __DIR__.'/../resources/lang' => lang_path('vendor/indie-notifications'),
        ], 'indie-notifications');
    }
}
