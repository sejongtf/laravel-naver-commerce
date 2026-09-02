<?php

namespace Sejongtf\LaravelNaverCommerce\Tests\Integration;

use Dotenv\Dotenv;
use Orchestra\Testbench\TestCase as Orchestra;
use Sejongtf\LaravelNaverCommerce\NaverCommerceServiceProvider;

/**
 * 실제 커머스API를 호출하는 통합 테스트. 패키지 루트 .env 에 자격증명이 없으면 skip 된다.
 * 운영 스토어를 대상으로 하므로 읽기 전용 엔드포인트만 호출한다.
 */
abstract class IntegrationTestCase extends Orchestra
{
    protected static ?array $env = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (empty($this->credentials()['NAVER_COMMERCE_CLIENT_ID']) || empty($this->credentials()['NAVER_COMMERCE_CLIENT_SECRET'])) {
            $this->markTestSkipped('NAVER_COMMERCE_CLIENT_ID / NAVER_COMMERCE_CLIENT_SECRET 이 .env 에 없어 통합 테스트를 건너뜁니다.');
        }
    }

    protected function getPackageProviders($app): array
    {
        return [NaverCommerceServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $env = $this->credentials();

        $app['config']->set('cache.default', 'array');
        $app['config']->set('naver-commerce.client_id', $env['NAVER_COMMERCE_CLIENT_ID'] ?? null);
        $app['config']->set('naver-commerce.client_secret', $env['NAVER_COMMERCE_CLIENT_SECRET'] ?? null);
    }

    protected function credentials(): array
    {
        if (static::$env === null) {
            $root = dirname(__DIR__, 2);
            static::$env = is_file($root.'/.env')
                ? Dotenv::createArrayBacked($root, '.env')->safeLoad()
                : [];
        }

        return static::$env;
    }
}
