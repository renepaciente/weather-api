<?php

namespace App\Providers;

use App\Contracts\WeatherProvider;
use App\Services\OpenWeatherClient;
use Illuminate\Support\ServiceProvider;

class WeatherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherProvider::class, function () {
            return new OpenWeatherClient(
                (string) config('services.openweathermap.key'),
                rtrim((string) config('services.openweathermap.base_url'), '/'),
            );
        });
    }
}
