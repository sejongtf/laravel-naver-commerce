<?php

namespace Sejongtf\LaravelNaverCommerce\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Sejongtf\LaravelNaverCommerce\Auth\TokenManager;
use Sejongtf\LaravelNaverCommerce\Exceptions\ApiException;
use Sejongtf\LaravelNaverCommerce\Exceptions\AuthenticationException;
use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;

/**
 * 인증·재시도·오류 변환을 담당하는 저수준 HTTP 클라이언트.
 */
class Client
{
    protected string $tokenType;

    protected ?string $accountId;

    protected ?Response $lastResponse = null;

    public function __construct(
        protected HttpFactory $http,
        protected TokenManager $tokens,
        protected array $config,
        ?string $tokenType = null,
        ?string $accountId = null,
    ) {
        $this->tokenType = strtoupper($tokenType ?? ($config['token']['type'] ?? TokenManager::TYPE_SELF));
        $this->accountId = $accountId ?? ($config['token']['account_id'] ?? null);
    }

    /**
     * 특정 판매자(SELLER 토큰)의 리소스를 호출하는 클라이언트 복제본.
     */
    public function forSeller(string $accountId): static
    {
        return new static($this->http, $this->tokens, $this->config, TokenManager::TYPE_SELLER, $accountId);
    }

    /**
     * 자기 애플리케이션(SELF 토큰) 리소스를 호출하는 클라이언트 복제본.
     */
    public function asSelf(): static
    {
        return new static($this->http, $this->tokens, $this->config, TokenManager::TYPE_SELF, null);
    }

    public function tokenType(): string
    {
        return $this->tokenType;
    }

    public function accountId(): ?string
    {
        return $this->accountId;
    }

    public function tokens(): TokenManager
    {
        return $this->tokens;
    }

    /**
     * 마지막 응답(헤더 확인용).
     */
    public function lastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $data = [], array $query = []): Response
    {
        return $this->request('POST', $path, ['json' => $data, 'query' => $query]);
    }

    public function put(string $path, array $data = [], array $query = []): Response
    {
        return $this->request('PUT', $path, ['json' => $data, 'query' => $query]);
    }

    public function patch(string $path, array $data = [], array $query = []): Response
    {
        return $this->request('PATCH', $path, ['json' => $data, 'query' => $query]);
    }

    public function delete(string $path, array $query = []): Response
    {
        return $this->request('DELETE', $path, ['query' => $query]);
    }

    /**
     * 요청을 전송한다.
     *
     * @param  array{query?: array, json?: array, multipart?: array<int, array{name: string, contents: mixed, filename?: string|null, headers?: array}>, headers?: array}  $options
     *
     * @throws NaverCommerceException
     */
    public function request(string $method, string $path, array $options = []): Response
    {
        $method = strtoupper($method);
        $url = $this->url($path, $options['query'] ?? []);

        $maxRetries = max(0, (int) ($this->config['retry']['times'] ?? 0));
        $sleepMs = max(0, (int) ($this->config['retry']['sleep_ms'] ?? 0));

        $attempt = 0;
        $authRetried = false;

        while (true) {
            try {
                $response = $this->send($method, $url, $options);
            } catch (ConnectionException $e) {
                if ($attempt < $maxRetries) {
                    $attempt++;
                    $this->sleep($sleepMs * $attempt);

                    continue;
                }

                throw new ApiException('Naver Commerce API 연결 오류: '.$e->getMessage(), 0, $e);
            }

            $this->lastResponse = $response;

            if ($response->successful()) {
                return $response;
            }

            if ($response->status() === 401 && ! $authRetried && $this->isTokenExpired($response)) {
                $authRetried = true;
                $this->tokens->forget($this->tokenType, $this->accountId);

                continue;
            }

            if ($response->serverError() && $attempt < $maxRetries) {
                $attempt++;
                $this->sleep($sleepMs * $attempt);

                continue;
            }

            throw NaverCommerceException::fromResponse($response);
        }
    }

    protected function send(string $method, string $url, array $options): Response
    {
        $pending = $this->pendingRequest();

        if (! empty($options['headers'])) {
            $pending = $pending->withHeaders($options['headers']);
        }

        if (! empty($options['multipart'])) {
            foreach ($options['multipart'] as $part) {
                $pending = $pending->attach(
                    $part['name'],
                    $part['contents'],
                    $part['filename'] ?? null,
                    $part['headers'] ?? [],
                );
            }

            // 빈 multipart 옵션을 넘겨야 attach() 로 추가한 파일이 병합된다.
            return $pending->send($method, $url, ['multipart' => []]);
        }

        if (array_key_exists('json', $options) && $options['json'] !== null) {
            return $pending->send($method, $url, [
                'json' => QueryString::normalizeBody($options['json']),
            ]);
        }

        return $pending->send($method, $url);
    }

    protected function pendingRequest(): PendingRequest
    {
        return $this->http
            ->baseUrl((string) $this->config['base_url'])
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->connectTimeout((int) ($this->config['connect_timeout'] ?? 10))
            ->acceptJson()
            ->withToken($this->tokens->token($this->tokenType, $this->accountId));
    }

    protected function url(string $path, array $query): string
    {
        $path = '/'.ltrim($path, '/');
        $qs = QueryString::build($query);

        return $qs === '' ? $path : $path.'?'.$qs;
    }

    protected function isTokenExpired(Response $response): bool
    {
        $body = $response->json();

        return is_array($body) && ($body['code'] ?? null) === AuthenticationException::GATEWAY_AUTHN;
    }

    protected function sleep(int $ms): void
    {
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }
}
