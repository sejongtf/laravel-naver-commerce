<?php

namespace Sejongtf\LaravelNaverCommerce\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class NaverCommerceException extends RuntimeException
{
    protected ?Response $response = null;

    protected ?string $errorCode = null;

    protected ?string $traceId = null;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * HTTP 응답으로부터 예외를 생성한다. 상태 코드에 따라 적절한 하위 클래스를 반환한다.
     */
    public static function fromResponse(Response $response): static
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        $status = $response->status();
        $errorCode = isset($body['code']) ? (string) $body['code'] : null;
        $message = isset($body['message']) ? (string) $body['message'] : ($response->reason() ?: 'Naver Commerce API request failed');
        $traceId = isset($body['traceId']) ? (string) $body['traceId'] : $response->header('GNCP-GW-Trace-ID');

        $class = match (true) {
            $status === 401 => AuthenticationException::class,
            $status === 429 => RateLimitException::class,
            default => ApiException::class,
        };

        if (static::class !== self::class && ! is_a($class, static::class, true)) {
            $class = static::class;
        }

        $exception = new $class(sprintf('[%d%s] %s', $status, $errorCode ? ' '.$errorCode : '', $message), $status);
        $exception->response = $response;
        $exception->errorCode = $errorCode;
        $exception->traceId = $traceId ?: null;

        return $exception;
    }

    public function response(): ?Response
    {
        return $this->response;
    }

    public function status(): ?int
    {
        return $this->response?->status();
    }

    /**
     * 게이트웨이(GW.*) 또는 API 서버 오류 코드.
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function traceId(): ?string
    {
        return $this->traceId;
    }

    /**
     * 응답 본문 전체(디코딩된 배열).
     */
    public function errors(): array
    {
        $body = $this->response?->json();

        return is_array($body) ? $body : [];
    }

    public function isGatewayError(): bool
    {
        return $this->errorCode !== null && str_starts_with($this->errorCode, 'GW.');
    }
}
