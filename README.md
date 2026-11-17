# dashboard-kit-addon-request-id


[![Packagist Version](https://img.shields.io/packagist/v/rafalmasiarek/dashboard-kit-addon-request-id)](https://packagist.org/packages/rafalmasiarek/dashboard-kit-addon-request-id)
[![Total Downloads](https://img.shields.io/packagist/dt/rafalmasiarek/dashboard-kit-addon-request-id)](https://packagist.org/packages/rafalmasiarek/dashboard-kit-addon-request-id)
[![PHP](https://img.shields.io/packagist/php-v/rafalmasiarek/dashboard-kit-addon-request-id)](https://packagist.org/packages/rafalmasiarek/dashboard-kit-addon-request-id)
[![License](https://img.shields.io/badge/license-BUSL--1.1-red)](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/LICENSE)







Per-request correlation ID addon for [dashboard-kit](https://github.com/rafalmasiarek/php-dashboard-kit).

Generates or propagates a request ID from an HTTP header and injects `req.id` into every Monolog log record, making it possible to trace all log entries for a single request across channels.







## License

![License: BUSL-1.1](https://img.shields.io/badge/license-BUSL--1.1-red)

Business Source License 1.1.

Source available; commercial use restricted until 2099-01-01.




See [LICENSE](LICENSE) for full details.

For alternative licensing, [contact us](https://masiarek.pl/contact/?af_subject=Commercial+license+%E2%80%94+dashboard-kit-addon-request-id&af_message=Hello%2C+I+am+interested+in+a+commercial+license+for+dashboard-kit-addon-request-id.).



## Authors

- Rafał Masiarek | [website](https://masiarek.pl) | [github](https://github.com/rafalmasiarek)




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


## Index

### Classes

- [`RequestIdAddon`](#requestidaddon) — `rafalmasiarek\DashboardKitRequestId`
- [`RequestIdMiddleware`](#requestidmiddleware) — `rafalmasiarek\DashboardKitRequestId`
- [`RequestProcessor`](#requestprocessor) — `rafalmasiarek\DashboardKitRequestId`

## `RequestIdAddon`

*class* — `rafalmasiarek\DashboardKitRequestId\RequestIdAddon` · [source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestIdAddon.php#L29)

Dashboard Kit addon that wires per-request correlation ID logging. Registers RequestProcessor into every Monolog channel and adds RequestIdMiddleware as the outermost Slim middleware so every log entry carries request_id regardless of where it is written. Usage in public/index.php (after Dashboard::create(), before run()): RequestIdAddon::register($dashboard->getApp(), $dashboard->getContainer(), [ 'header' => 'X-Request-ID', ]); Configuration options: header (string) HTTP header to read/propagate. Default: X-Request-ID. Set to 'X-Amzn-Trace-Id' when running behind AWS ALB, or to the header your nginx/Caddy injects.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`register()`](#requestidaddon-register) | public static | `void` | Registers the addon into an existing Dashboard application. Pushes RequestProcessor into all logger.* channels found in the container and adds RequestIdMiddleware as the outermost middleware layer. |

<details>
<summary><code>register()</code></summary>

#### `RequestIdAddon register()`

[source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestIdAddon.php#L41)

```php
public static function register(App $app, ContainerInterface $container, array $config): void
```

Registers the addon into an existing Dashboard application. Pushes RequestProcessor into all logger.* channels found in the container and adds RequestIdMiddleware as the outermost middleware layer.

| Param | Type | Description |
|---|---|---|
| `$app` | `App` | Slim application instance. |
| `$container` | `ContainerInterface` | PHP-DI container from Dashboard::getContainer(). |
| `$config` | `array<string, mixed>` | Optional configuration overrides. |

</details>

## `RequestIdMiddleware`

*class* — `rafalmasiarek\DashboardKitRequestId\RequestIdMiddleware` · [source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestIdMiddleware.php#L20)

PSR-15 middleware that assigns a correlation ID to every request. Reads the ID from the configured request header (default: X-Request-ID); generates a UUID v4 if the header is absent. Calls RequestProcessor::boot() so every Monolog record for the request carries the same ID. Propagates the resolved ID back to the caller via the same response header.

implements `MiddlewareInterface`

**Depends on:** [`RequestProcessor`](#requestprocessor)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#requestidmiddleware-__construct) | public | — | — |
| [`process()`](#requestidmiddleware-process) | public | `ResponseInterface` | Boots the request context and passes the request to the next handler. |
| [`generateUuid()`](#requestidmiddleware-generateuuid) | private | `string` | Generates a random UUID v4. |

<details>
<summary><code>__construct()</code></summary>

#### `RequestIdMiddleware __construct()`

[source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestIdMiddleware.php#L29)

```php
public function __construct(RequestProcessor $processor, string $header)
```

| Param | Type | Description |
|---|---|---|
| `$processor` | [`RequestProcessor`](#requestprocessor) | Monolog processor to boot with the resolved request ID. |
| `$header` | `string` | HTTP header name to read from and write to. |

</details>

<details>
<summary><code>process()</code></summary>

#### `RequestIdMiddleware process()`

[source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestIdMiddleware.php#L42)

```php
public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
```

Boots the request context and passes the request to the next handler.

| Param | Type | Description |
|---|---|---|
| `$request` | `ServerRequestInterface` | PSR-7 server request. |
| `$handler` | `RequestHandlerInterface` | Next middleware or route handler. |

**Returns:** `ResponseInterface`

</details>

<details>
<summary><code>generateUuid()</code></summary>

#### `RequestIdMiddleware generateUuid()`

[source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestIdMiddleware.php#L60)

```php
private function generateUuid(): string
```

Generates a random UUID v4.

**Returns:** `string`

</details>

## `RequestProcessor`

*class* — `rafalmasiarek\DashboardKitRequestId\RequestProcessor` · [source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestProcessor.php#L18)

Monolog processor that injects a per-request correlation ID into every log record. Populated per-request by calling boot() from RequestIdMiddleware. Log output example: req.id=550e8400-e29b-41d4-a716-446655440000  user_id=3  email=…

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$requestId` | `string` | private | Correlation ID for the current request. |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`boot()`](#requestprocessor-boot) | public | `void` | Populates the correlation ID from the incoming request. Called once per request by RequestIdMiddleware. |
| [`getRequestId()`](#requestprocessor-getrequestid) | public | `string` | Returns the current request_id, or an empty string when not yet set. |
| [`__invoke()`](#requestprocessor-__invoke) | public | `LogRecord` | Invoked by Monolog for every log record. Injects req.id into the record context. Log-call context takes priority — the processor only fills in the key if not already present. |

<details>
<summary><code>boot()</code></summary>

#### `RequestProcessor boot()`

[source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestProcessor.php#L31)

```php
public function boot(string $requestId, ServerRequestInterface $request): void
```

Populates the correlation ID from the incoming request. Called once per request by RequestIdMiddleware.

| Param | Type | Description |
|---|---|---|
| `$requestId` | `string` | Correlation ID (from header or freshly generated). |
| `$request` | `ServerRequestInterface` | Incoming PSR-7 request (reserved for future use). |

</details>

<details>
<summary><code>getRequestId()</code></summary>

#### `RequestProcessor getRequestId()`

[source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestProcessor.php#L41)

```php
public function getRequestId(): string
```

Returns the current request_id, or an empty string when not yet set.

**Returns:** `string`

</details>

<details>
<summary><code>__invoke()</code></summary>

#### `RequestProcessor __invoke()`

[source](https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id/blob/main/src/RequestProcessor.php#L55)

```php
public function __invoke(LogRecord $record): LogRecord
```

Invoked by Monolog for every log record. Injects req.id into the record context. Log-call context takes priority — the processor only fills in the key if not already present.

| Param | Type | Description |
|---|---|---|
| `$record` | `LogRecord` | — |

**Returns:** `LogRecord`

</details>




<!-- references -->

[repo_link]: https://github.com/rafalmasiarek/php-dashboard-kit-addon-request-id
