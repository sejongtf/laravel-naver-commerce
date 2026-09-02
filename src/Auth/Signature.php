<?php

namespace Sejongtf\LaravelNaverCommerce\Auth;

use Sejongtf\LaravelNaverCommerce\Exceptions\AuthenticationException;

/**
 * 인증 토큰 발급용 전자서명(client_secret_sign) 생성기.
 *
 * password = "{client_id}_{timestamp}", salt = client_secret 으로 bcrypt 해싱 후 base64 인코딩한다.
 */
class Signature
{
    public static function generate(string $clientId, string $clientSecret, int $timestampMs): string
    {
        if ($clientId === '' || $clientSecret === '') {
            throw new AuthenticationException('Naver Commerce client_id / client_secret 이 설정되지 않았습니다.');
        }

        $hashed = @crypt($clientId.'_'.$timestampMs, $clientSecret);

        if (! str_starts_with($hashed, '$2')) {
            throw new AuthenticationException('전자서명 생성에 실패했습니다. client_secret 이 bcrypt salt 형식($2a$10$...)인지 확인하세요.');
        }

        return base64_encode($hashed);
    }

    /**
     * 현재 시각의 밀리초 단위 Unix 타임스탬프.
     */
    public static function timestamp(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
