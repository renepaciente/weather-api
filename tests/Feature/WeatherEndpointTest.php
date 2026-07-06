<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherEndpointTest extends TestCase
{
    public function test_it_returns_current_weather_for_a_city(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response($this->fakeWeather(), 200),
        ]);

        $this->getJson('/api/weather/Manila')
            ->assertOk()
            ->assertJsonStructure(['city', 'temperature', 'description', 'timestamp', 'source'])
            ->assertJson([
                'city' => 'Manila',
                'temperature' => 30.5,
                'description' => 'scattered clouds',
                'source' => 'external',
            ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'units=metric'));
    }

    public function test_unknown_city_returns_404(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response(['cod' => '404', 'message' => 'city not found'], 404),
        ]);

        $this->getJson('/api/weather/Atlantis')
            ->assertNotFound()
            ->assertExactJson(['error' => 'City not found']);
    }

    public function test_invalid_api_key_returns_502(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response(['cod' => 401, 'message' => 'Invalid API key'], 401),
        ]);

        $this->getJson('/api/weather/Manila')
            ->assertStatus(502)
            ->assertExactJson(['error' => 'Weather service is not configured correctly']);
    }

    public function test_upstream_error_returns_503(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response('Internal Server Error', 500),
        ]);

        $this->getJson('/api/weather/Manila')
            ->assertStatus(503)
            ->assertExactJson(['error' => 'Weather service unavailable']);
    }

    public function test_timeout_returns_503(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->getJson('/api/weather/Manila')
            ->assertStatus(503)
            ->assertExactJson(['error' => 'Weather service unavailable']);
    }

    public function test_invalid_city_names_are_rejected(): void
    {
        Http::fake();

        // blank
        $this->getJson('/api/weather/%20%20')->assertStatus(422);

        // too long
        $this->getJson('/api/weather/'.str_repeat('a', 101))->assertStatus(422);

        // junk characters
        $this->getJson('/api/weather/'.rawurlencode('Manila<script>'))
            ->assertStatus(422)
            ->assertExactJson(['error' => 'Invalid city name']);

        Http::assertNothingSent();
    }

    private function fakeWeather(): array
    {
        return [
            'name' => 'Manila',
            'dt' => 1719900000,
            'main' => ['temp' => 30.5],
            'weather' => [['description' => 'scattered clouds']],
        ];
    }
}
