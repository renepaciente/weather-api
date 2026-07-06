<?php

namespace App\DataTransfers;

use Carbon\CarbonImmutable;

readonly class WeatherData
{
    public function __construct(
        public string $city,
        public float $temperature,
        public string $description,
        public string $timestamp,
    ) {
    }

    public static function fromApiResponse(array $payload): self
    {
        $timestamp = isset($payload['dt'])
            ? CarbonImmutable::createFromTimestampUTC((int) $payload['dt'])->toIso8601String()
            : CarbonImmutable::now()->toIso8601String();

        return new self(
            city: (string) $payload['name'],
            temperature: round((float) $payload['main']['temp'], 2),
            description: (string) ($payload['weather'][0]['description'] ?? ''),
            timestamp: $timestamp,
        );
    }

    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'temperature' => $this->temperature,
            'description' => $this->description,
            'timestamp' => $this->timestamp,
        ];
    }
}
