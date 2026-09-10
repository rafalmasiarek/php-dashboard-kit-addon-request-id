# dashboard-kit-addon-request-id

Per-request correlation ID addon for [dashboard-kit](https://github.com/rafalmasiarek/php-dashboard-kit).

Generates or propagates a request ID from an HTTP header and injects `req.id` into every Monolog log record, making it possible to trace all log entries for a single request across channels.

## Installation

```bash
composer require rafalmasiarek/dashboard-kit-addon-request-id
```

## Usage

```php
use rafalmasiarek\DashboardKit\Dashboard;
use rafalmasiarek\DashboardKitRequestId\RequestIdAddon;

$dashboard = Dashboard::create(__DIR__ . '/../', [ /* config */ ]);

RequestIdAddon::register($dashboard->getApp(), $dashboard->getContainer());

$dashboard->run();
```

## Configuration

```php
RequestIdAddon::register($dashboard->getApp(), $dashboard->getContainer(), [
    'header' => 'X-Request-ID',  // default; use 'X-Amzn-Trace-Id' behind AWS ALB
]);
```

| Option   | Default        | Description                                     |
|----------|----------------|-------------------------------------------------|
| `header` | `X-Request-ID` | HTTP header to read the ID from and propagate back |

## Log output

Every log record in `app`, `audit`, and `error` channels receives:

```
req.id=550e8400-e29b-41d4-a716-446655440000
```

When the header is absent, a UUID v4 is generated per request.

## Cross-addon correlation

`register()` saves the processor in the container as `log.processor.request_id`. Other addons that create their own Monolog loggers (e.g. `dashboard-kit-addon-geoip`) pull this processor automatically to include `req.id` in their own log channels.

Register this addon before any other addon that should include `req.id` in its logs:

```php
RequestIdAddon::register($dashboard->getApp(), $container);  // first
GeoIpAddon::register($dashboard->getApp(), $container);       // picks up req.id automatically
```

## Requirements

- PHP 8.2+
- dashboard-kit
- monolog/monolog ^3

## License

Business Source License 1.1 — see [LICENSE](LICENSE).
For alternative licensing, [contact us](https://masiarek.pl/contact/?af_subject=Commercial+license+%E2%80%94+dashboard-kit-addon-request-id&af_message=Hello%2C+I+am+interested+in+a+commercial+license+for+dashboard-kit-addon-request-id.).
