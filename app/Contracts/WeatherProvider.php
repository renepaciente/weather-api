<?php

namespace App\Contracts;

use App\DataTransfers\WeatherData;

interface WeatherProvider
{
    /**
     * @throws \App\Exceptions\WeatherApiException
     */
    public function getCurrentWeather(string $city): WeatherData;
}
