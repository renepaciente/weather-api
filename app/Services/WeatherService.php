<?php

namespace App\Services;


use App\Contracts\WeatherProvider;
use App\Exceptions\WeatherApiException;
use Illuminate\Support\Facades\Cache;

class WeatherService
{
    private const CACHE_TTL = 600; // 10 minutes

    public function __construct(private WeatherProvider $provider)
    {
    }

    public function getWeather(string $city): array
    {
        $city = $this->normalize($city);
        return $this->provider->getCurrentWeather($city)->toArray() + ['source' => 'external'];
    }

    public function getCachedWeather(string $city): array
    {
        $city = $this->normalize($city);
        $key = 'weather.'.md5($city);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached + ['source' => 'cache'];
        }
        $data = $this->provider->getCurrentWeather($city)->toArray();
        Cache::put($key, $data, self::CACHE_TTL);
        return $data + ['source' => 'external'];
    }

    private function normalize(string $city): string
    {
        $city = mb_strtolower(trim(rawurldecode($city)));

        if ($city === '' || mb_strlen($city) > 100 || ! preg_match("/^[\p{L}\p{M}\s.,'-]+$/u", $city)) {
            throw WeatherApiException::invalidCity();
        }

        return $city;
    }
}
