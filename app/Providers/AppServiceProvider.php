<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** O app é todo em português, independente do APP_LOCALE do .env */
    public const LOCALE = 'pt_BR';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mensagens de validação em pt_BR (pacote laravel-pt-br-localization)
        $this->app->setLocale(self::LOCALE);

        // Datas: "quinta-feira, 5 de novembro", "SEG", "TER"…
        Carbon::setLocale(self::LOCALE);
        CarbonImmutable::setLocale(self::LOCALE);
    }
}
