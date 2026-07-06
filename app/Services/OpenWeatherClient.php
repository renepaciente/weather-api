<?php

namespace App\Services;

use App\Contracts\WeatherProvider;
use App\DataTransfers\WeatherData;
use App\Exceptions\WeatherApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWeatherClient implements WeatherProvider
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
    ) {
    }

    public function getCurrentWeather(string $city): WeatherData
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 100, function ($exception) {
                    // only retry network errors and 5xx, a 404/401 won't get better
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError());
                }, throw: false)
                ->get($this->baseUrl.'/weather', [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenWeatherMap request failed: '.$e->getMessage());
            throw WeatherApiException::serviceUnavailable();
        }

        if ($response->status() === 404) {
            throw WeatherApiException::cityNotFound();
        }

        if ($response->status() === 401 || $response->status() === 403) {
            Log::error('OpenWeatherMap rejected the API key');
            throw WeatherApiException::badGateway();
        }

        if ($response->failed()) {
            Log::warning("OpenWeatherMap returned {$response->status()} for city {$city}");
            throw WeatherApiException::serviceUnavailable();
        }

        return WeatherData::fromApiResponse($response->json());
    }
}
