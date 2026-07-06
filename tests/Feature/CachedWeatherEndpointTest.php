<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CachedWeatherEndpointTest extends TestCase
{
    public function test_second_call_is_served_from_cache(): void
    {
        $this->fakeWeatherApi();

        $first = $this->getJson('/api/weather/Manila/cached');
        $first->assertOk()->assertJson(['source' => 'external']);

        $second = $this->getJson('/api/weather/Manila/cached');
        $second->assertOk()->assertJson(['source' => 'cache']);

        // same payload apart from the source flag
        $this->assertSame(
            collect($first->json())->except('source')->all(),
            collect($second->json())->except('source')->all(),
        );

        Http::assertSentCount(1);
    }

    public function test_cache_expires_after_10_minutes(): void
    {
        $this->fakeWeatherApi();

        $this->getJson('/api/weather/Manila/cached')->assertJson(['source' => 'external']);

        $this->travel(11)->minutes();

        $this->getJson('/api/weather/Manila/cached')->assertJson(['source' => 'external']);

        Http::assertSentCount(2);
    }

    public function test_cache_key_ignores_case_and_whitespace(): void
    {
        $this->fakeWeatherApi();

        $this->getJson('/api/weather/Manila/cached')->assertJson(['source' => 'external']);
        $this->getJson('/api/weather/manila/cached')->assertJson(['source' => 'cache']);
        $this->getJson('/api/weather/%20MANILA%20/cached')->assertJson(['source' => 'cache']);

        Http::assertSentCount(1);
    }

    public function test_failed_lookups_are_not_cached(): void
    {
        // two 500s because the client retries once, then a good response
        Http::fake([
            'api.openweathermap.org/*' => Http::sequence()
                ->push('Internal Server Error', 500)
                ->push('Internal Server Error', 500)
                ->push($this->fakeWeather(), 200),
        ]);

        $this->getJson('/api/weather/Manila/cached')->assertStatus(503);

        $this->getJson('/api/weather/Manila/cached')
            ->assertOk()
            ->assertJson(['source' => 'external']);
    }

    private function fakeWeatherApi(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response($this->fakeWeather(), 200),
        ]);
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
