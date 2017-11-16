<?php

namespace Hacktivista\TransToJson;

use Hacktivista\TransToJson\Console\TranslationsToJsonCommand;
use Illuminate\Support\ServiceProvider;

class TransToJsonServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslationsToJsonCommand::class,
            ]);
        }
    }
}
