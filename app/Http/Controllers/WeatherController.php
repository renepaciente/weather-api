<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\WeatherService;

class WeatherController extends Controller
{

    public function __construct(private WeatherService $weather)
    {
    }


    public function show(string $city): JsonResponse
    {
        return response()->json($this->weather->getWeather($city));
    }

    public function cached(string $city): JsonResponse
    {
        return response()->json($this->weather->getCachedWeather($city));
    }
}
