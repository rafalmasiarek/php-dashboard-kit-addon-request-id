<?php

namespace rafalmasiarek\DashboardKitRequestId;

use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Dashboard Kit addon that wires per-request correlation ID logging.
 *
 * Registers RequestProcessor into every Monolog channel and adds
 * RequestIdMiddleware as the outermost Slim middleware so every log entry
 * carries request_id regardless of where it is written.
 *
 * Usage in public/index.php (after Dashboard::create(), before run()):
 *
 *   RequestIdAddon::register($dashboard->getApp(), $dashboard->getContainer(), [
 *       'header' => 'X-Request-ID',
 *   ]);
 *
 * Configuration options:
 *   header (string) HTTP header to read/propagate. Default: X-Request-ID.
 *                   Set to 'X-Amzn-Trace-Id' when running behind AWS ALB,
 *                   or to the header your nginx/Caddy injects.
 *
 * @package rafalmasiarek\DashboardKitRequestId
 */
class RequestIdAddon
{
    /**
     * Registers the addon into an existing Dashboard application.
     *
     * Pushes RequestProcessor into all logger.* channels found in the container
     * and adds RequestIdMiddleware as the outermost middleware layer.
     *
     * @param App                  $app       Slim application instance.
     * @param ContainerInterface   $container PHP-DI container from Dashboard::getContainer().
     * @param array<string, mixed> $config    Optional configuration overrides.
     */
    public static function register(App $app, ContainerInterface $container, array $config = []): void
    {
        if (!\class_exists(\rafalmasiarek\DashboardKit\Dashboard::class)) {
            throw new \LogicException(
                static::class . ' is a dashboard-kit addon and requires rafalmasiarek/dashboard-kit. '
                . 'Run: composer require rafalmasiarek/dashboard-kit'
            );
        }

        $header    = (string) ($config['header'] ?? 'X-Request-ID');
        $processor = new RequestProcessor();

        foreach (['app', 'audit', 'error'] as $channel) {
            try {
                $logger = $container->get('logger.' . $channel);
                if ($logger instanceof Logger) {
                    $logger->pushProcessor($processor);
                }
            } catch (\Throwable) {
            }
        }

        $container->set('log.processor.request_id', static fn() => $processor);

        $app->add(new RequestIdMiddleware($processor, $header));
    }
}
