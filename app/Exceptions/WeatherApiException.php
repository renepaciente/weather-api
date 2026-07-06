<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WeatherApiException extends Exception
{
    public function __construct(string $message, private int $status = 500)
    {
        parent::__construct($message);
    }

    public static function invalidCity(): self
    {
        return new self('Invalid city name', 422);
    }

    public static function cityNotFound(): self
    {
        return new self('City not found', 404);
    }

    public static function badGateway(): self
    {
        return new self('Weather service is not configured correctly', 502);
    }

    public static function serviceUnavailable(): self
    {
        return new self('Weather service unavailable', 503);
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->getMessage()], $this->status);
    }
}
