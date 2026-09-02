<?php

namespace Sejongtf\LaravelNaverCommerce\Auth;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Sejongtf\LaravelNaverCommerce\Exceptions\AuthenticationException;

/**
 * OAuth2 Client Credentials 토큰 발급 및 캐시.
 */
class TokenManager
{
    public const TYPE_SELF = 'SELF';

    public const TYPE_SELLER = 'SELLER';

    public const TOKEN_PATH = '/v1/oauth2/token';

    /** @var array<string, string> 프로세스 내 토큰 캐시 */
    protected array $memory = [];

    public function __construct(
        protected HttpFactory $http,
        protected CacheFactory $cache,
        protected array $config,
    ) {}

    /**
     * 유효한 액세스 토큰을 반환한다(캐시 우선).
     */
    public function token(string $type = self::TYPE_SELF, ?string $accountId = null): string
    {
        $key = $this->cacheKey($type, $accountId);

        if (isset($this->memory[$key])) {
            return $this->memory[$key];
        }

        $cached = $this->store()->get($key);

        if (is_string($cached) && $cached !== '') {
            return $this->memory[$key] = $cached;
        }

        $issued = $this->issue($type, $accountId);

        $ttl = max(1, $issued['expires_in'] - $this->ttlMargin());
        $this->store()->put($key, $issued['access_token'], $ttl);

        return $this->memory[$key] = $issued['access_token'];
    }

    /**
     * 캐시된 토큰을 폐기한다(401 GW.AUTHN 수신 시 호출).
     */
    public function forget(string $type = self::TYPE_SELF, ?string $accountId = null): void
    {
        $key = $this->cacheKey($type, $accountId);

        unset($this->memory[$key]);
        $this->store()->forget($key);
    }

    /**
     * 토큰 발급 API를 호출한다. 캐시하지 않는다.
     *
     * @return array{access_token: string, expires_in: int, token_type: string}
     */
    public function issue(string $type = self::TYPE_SELF, ?string $accountId = null): array
    {
        $type = strtoupper($type);

        if ($type === self::TYPE_SELLER && ($accountId === null || $accountId === '')) {
            throw new AuthenticationException('SELLER 타입 토큰 발급에는 account_id 가 필요합니다.');
        }

        $clientId = (string) ($this->config['client_id'] ?? '');
        $clientSecret = (string) ($this->config['client_secret'] ?? '');
        $timestamp = Signature::timestamp();

        $payload = [
            'client_id' => $clientId,
            'timestamp' => $timestamp,
            'grant_type' => 'client_credentials',
            'client_secret_sign' => Signature::generate($clientId, $clientSecret, $timestamp),
            'type' => $type,
        ];

        if ($type === self::TYPE_SELLER) {
            $payload['account_id'] = $accountId;
        }

        try {
            $response = $this->http
                ->baseUrl((string) $this->config['base_url'])
                ->timeout((int) ($this->config['timeout'] ?? 30))
                ->connectTimeout((int) ($this->config['connect_timeout'] ?? 10))
                ->acceptJson()
                ->asForm()
                ->post(self::TOKEN_PATH, $payload);
        } catch (ConnectionException $e) {
            throw new AuthenticationException('토큰 발급 요청 중 연결 오류: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw AuthenticationException::fromResponse($response);
        }

        $body = $response->json();

        if (! is_array($body) || empty($body['access_token'])) {
            throw new AuthenticationException('토큰 발급 응답에 access_token 이 없습니다.');
        }

        return [
            'access_token' => (string) $body['access_token'],
            'expires_in' => (int) ($body['expires_in'] ?? 0),
            'token_type' => (string) ($body['token_type'] ?? 'Bearer'),
        ];
    }

    public function cacheKey(string $type, ?string $accountId): string
    {
        $prefix = $this->config['token']['cache_prefix'] ?? 'naver-commerce:token';
        $type = strtoupper($type);

        return sprintf(
            '%s:%s:%s:%s',
            $prefix,
            substr(sha1((string) ($this->config['client_id'] ?? '')), 0, 12),
            $type,
            $type === self::TYPE_SELLER ? (string) $accountId : 'self',
        );
    }

    protected function store(): Repository
    {
        return $this->cache->store($this->config['token']['cache_store'] ?? null);
    }

    protected function ttlMargin(): int
    {
        return (int) ($this->config['token']['ttl_margin'] ?? 60);
    }
}
