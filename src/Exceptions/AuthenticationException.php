<?php

namespace Sejongtf\LaravelNaverCommerce\Exceptions;

class AuthenticationException extends NaverCommerceException
{
    public const GATEWAY_AUTHN = 'GW.AUTHN';

    /**
     * 토큰 만료로 인한 인증 실패인지 여부. 재발급 후 재시도 대상.
     */
    public function isTokenExpired(): bool
    {
        return $this->errorCode === self::GATEWAY_AUTHN;
    }
}
