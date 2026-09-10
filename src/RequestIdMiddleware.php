<?php

namespace rafalmasiarek\DashboardKitRequestId;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware that assigns a correlation ID to every request.
 *
 * Reads the ID from the configured request header (default: X-Request-ID);
 * generates a UUID v4 if the header is absent. Calls RequestProcessor::boot()
 * so every Monolog record for the request carries the same ID. Propagates the
 * resolved ID back to the caller via the same response header.
 *
 * @package rafalmasiarek\DashboardKitRequestId
 */
class RequestIdMiddleware implements MiddlewareInterface
{
    /** @var string Default HTTP header name for the correlation ID. */
    private const DEFAULT_HEADER = 'X-Request-ID';

    /**
     * @param RequestProcessor $processor Monolog processor to boot with the resolved request ID.
     * @param string           $header    HTTP header name to read from and write to.
     */
    public function __construct(
        private readonly RequestProcessor $processor,
        private readonly string           $header = self::DEFAULT_HEADER,
    ) {
    }

    /**
     * Boots the request context and passes the request to the next handler.
     *
     * @param  ServerRequestInterface  $request PSR-7 server request.
     * @param  RequestHandlerInterface $handler Next middleware or route handler.
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $request->getHeaderLine($this->header);
        if ($requestId === '') {
            $requestId = $this->generateUuid();
        }

        $this->processor->boot($requestId, $request);

        return $handler->handle($request)
            ->withHeader($this->header, $requestId);
    }

    /**
     * Generates a random UUID v4.
     *
     * @return string
     */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
