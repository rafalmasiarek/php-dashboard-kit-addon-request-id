<?php

namespace rafalmasiarek\DashboardKitRequestId;

use Monolog\LogRecord;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Monolog processor that injects a per-request correlation ID into every log record.
 *
 * Populated per-request by calling boot() from RequestIdMiddleware.
 *
 * Log output example:
 *   req.id=550e8400-e29b-41d4-a716-446655440000  user_id=3  email=…
 *
 * @package rafalmasiarek\DashboardKitRequestId
 */
class RequestProcessor
{
    /** @var string Correlation ID for the current request. */
    private string $requestId = '';

    /**
     * Populates the correlation ID from the incoming request.
     *
     * Called once per request by RequestIdMiddleware.
     *
     * @param string                 $requestId Correlation ID (from header or freshly generated).
     * @param ServerRequestInterface $request   Incoming PSR-7 request (reserved for future use).
     */
    public function boot(string $requestId, ServerRequestInterface $request): void
    {
        $this->requestId = $requestId;
    }

    /**
     * Returns the current request_id, or an empty string when not yet set.
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Invoked by Monolog for every log record.
     *
     * Injects req.id into the record context. Log-call context takes priority —
     * the processor only fills in the key if not already present.
     *
     * @param  LogRecord $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        if ($this->requestId === '') {
            return $record;
        }

        return $record->with(context: $record->context + ['req.id' => $this->requestId]);
    }
}
