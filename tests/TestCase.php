<?php

namespace Sejongtf\LaravelNaverCommerce\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use Sejongtf\LaravelNaverCommerce\Auth\TokenManager;
use Sejongtf\LaravelNaverCommerce\NaverCommerceServiceProvider;

abstract class TestCase extends Orchestra
{
    public const BASE = 'https://api.commerce.naver.com/external';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [NaverCommerceServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('naver-commerce.client_id', 'test-client-id');
        $app['config']->set('naver-commerce.client_secret', '$2a$10$abcdefghijklmnopqrstuv');
        $app['config']->set('naver-commerce.retry.sleep_ms', 0);
    }

    /**
     * 토큰 발급 응답 fake 정의.
     */
    protected function fakeToken(string $token = 'test-token', int $expiresIn = 10800): array
    {
        return [
            self::BASE.TokenManager::TOKEN_PATH => Http::response([
                'access_token' => $token,
                'expires_in' => $expiresIn,
                'token_type' => 'Bearer',
            ]),
        ];
    }

    /**
     * 토큰 fake + 추가 응답 fake 를 함께 등록한다.
     */
    protected function fakeApi(array $responses = []): void
    {
        Http::fake($this->fakeToken() + $responses);
    }

    protected function url(string $path): string
    {
        return self::BASE.$path;
    }

    /**
     * 토큰 발급 요청을 제외한 API 요청 중 조건에 맞는 요청이 있었는지 검증한다.
     */
    protected function assertApiSent(callable $callback): void
    {
        Http::assertSent(function (Request $request) use ($callback) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), TokenManager::TOKEN_PATH)) {
                return false;
            }

            return (bool) $callback($request);
        });
    }
}
