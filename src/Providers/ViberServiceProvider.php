<?php

namespace TheArdent\Drivers\Viber\Providers;

use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Studio\Providers\StudioServiceProvider;
use TheArdent\Drivers\Viber\Console\Commands\ViberRegisterCommand;
use TheArdent\Drivers\Viber\ViberDriver;


class ViberServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes(
                [
                    __DIR__ . '/../../stubs/viber.php' => config_path('botman/viber.php'),
                ]
            );

            $this->mergeConfigFrom(__DIR__ . '/../../stubs/viber.php', 'botman.viber');

            $this->commands(
                [
                    ViberRegisterCommand::class,
                ]
            );
        }
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(ViberDriver::class);
    }

    protected function isRunningInBotManStudio(): bool
    {
        return class_exists(StudioServiceProvider::class);
    }
}
