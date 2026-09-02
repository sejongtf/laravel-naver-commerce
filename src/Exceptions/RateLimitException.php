<?php

namespace Sejongtf\LaravelNaverCommerce\Exceptions;

class RateLimitException extends NaverCommerceException
{
    public const RATE_LIMIT = 'GW.RATE_LIMIT';

    public const QUOTA_LIMIT = 'GW.QUOTA_LIMIT';

    public function isQuotaLimit(): bool
    {
        return $this->errorCode === self::QUOTA_LIMIT;
    }

    /** 초당 최대 동시 요청 수 */
    public function replenishRate(): ?int
    {
        return $this->intHeader('GNCP-GW-RateLimit-Replenish-Rate');
    }

    /** 버스트 모드 최대 요청 수 */
    public function burstCapacity(): ?int
    {
        return $this->intHeader('GNCP-GW-RateLimit-Burst-Capacity');
    }

    /** 남은 동시 요청 수 */
    public function remaining(): ?int
    {
        return $this->intHeader('GNCP-GW-RateLimit-Remaining');
    }

    /** 쿼터 단위 시간(SECONDS | ROUND) */
    public function quotaPeriod(): ?string
    {
        $value = $this->response?->header('GNCP-GW-Quota-Period');

        return $value !== null && $value !== '' ? $value : null;
    }

    public function quotaLimit(): ?int
    {
        return $this->intHeader('GNCP-GW-Quota-Limit');
    }

    public function quotaRemaining(): ?int
    {
        return $this->intHeader('GNCP-GW-Quota-Remaining');
    }

    protected function intHeader(string $name): ?int
    {
        $value = $this->response?->header($name);

        return is_numeric($value) ? (int) $value : null;
    }
}
