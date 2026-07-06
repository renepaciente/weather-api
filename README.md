# Weather API

Laravel 12.62.0 take-home exam. Two endpoints that return current weather for a city from OpenWeatherMap, one live and one cached for 10 minutes.


## System requirements

- PHP 8.2 or higher, with the usual Laravel extensions enabled: `curl`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `json`, `tokenizer`, `xml`
- Composer 2.x
- An OpenWeatherMap API key (free tier is fine)
- No database, web server or Node.js needed - the built-in `php artisan serve` and the file cache driver cover everything


## Setup

```bash
git clone <repo-url>
cd weather-api
cp .env.example .env
composer install
php artisan serve
```

Then put your OpenWeatherMap API key in `.env`:

```
OPENWEATHERMAP_API_KEY=your_key_here
```

No database needed. The `.env.example` already contains an APP_KEY, and cache/sessions use the file driver.

## Running the tests

```bash
php artisan test
```

The tests don't hit the real API, everything goes through `Http::fake()`.

## Endpoints

**GET /api/weather/{city}** - always fetches live data:

```bash
curl http://127.0.0.1:8000/api/weather/Manila
```

```json
{
    "city": "Manila",
    "temperature": 30.5,
    "description": "scattered clouds",
    "timestamp": "2026-07-06T04:20:00+00:00",
    "source": "external"
}
```

**GET /api/weather/{city}/cached** - same data, cached for 10 minutes. The first call fetches from the API and returns `"source": "external"`, calls after that return `"source": "cache"` until the entry expires. The city is normalized (trimmed, lowercased, url-decoded) before building the cache key, so `Manila` and `manila` share the same entry.

```bash
curl http://127.0.0.1:8000/api/weather/manila/cached
```

Error responses all use the same shape, `{"error": "..."}`:

- unknown city -> `404 {"error": "City not found"}`
- invalid/missing OpenWeatherMap key -> `502` (this is a server config problem, so I don't pass the upstream 401 through to the client)
- timeout or 5xx from OpenWeatherMap -> `503 {"error": "Weather service unavailable"}`
- blank/too long/invalid city input -> `422 {"error": "Invalid city name"}`

## Approach

- The controller stays thin and just delegates to `WeatherService`, which handles city validation/normalization and the caching logic.
- The actual HTTP call lives in `OpenWeatherClient`, behind a `WeatherProvider` interface bound in `WeatherServiceProvider`. If we ever switch providers (or want to mock the client in a test) nothing outside that class changes.
- Responses from the API are mapped into a small readonly `WeatherData` DataTransfers instead of passing raw arrays around.
- All failure cases throw `WeatherApiException`, which knows how to render itself as JSON with the right status code. Guzzle exceptions and stack traces never reach the client.
- The client uses a 5 second timeout and one retry, but only for network errors and 5xx responses (retrying a 404 is pointless).
- Only the raw weather data is cached, the `source` field is added per request. Failed lookups are never cached.
- API key and base URL are read from `config/services.php`, no `env()` calls outside the config files.
