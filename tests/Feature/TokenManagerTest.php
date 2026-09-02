<?php

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelNaverCommerce\Auth\TokenManager;
use Sejongtf\LaravelNaverCommerce\Exceptions\AuthenticationException;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

/** @var TestCase $this */
it('issues a token with a signed form payload', function () {
    Http::fake($this->fakeToken('abc', 10800));

    $token = app(TokenManager::class)->token();

    expect($token)->toBe('abc');

    Http::assertSent(function (Request $request) {
        $data = $request->data();

        return $request->url() === $this->url('/v1/oauth2/token')
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $data['client_id'] === 'test-client-id'
            && $data['grant_type'] === 'client_credentials'
            && $data['type'] === 'SELF'
            && ! array_key_exists('account_id', $data)
            && is_numeric($data['timestamp'])
            && str_starts_with(base64_decode($data['client_secret_sign']), '$2a$10$abcdefghijklmnopqrstu');
    });
});

it('caches the token and reuses it', function () {
    Http::fake($this->fakeToken('cached', 3600));

    $manager = app(TokenManager::class);

    $manager->token();
    $manager->token();

    Http::assertSentCount(1);

    $key = $manager->cacheKey('SELF', null);
    expect(Cache::get($key))->toBe('cached');
});

it('applies the ttl margin when caching', function () {
    Http::fake($this->fakeToken('short', 120));

    $repository = Mockery::mock(Repository::class);
    $repository->shouldReceive('get')->once()->andReturnNull();
    $repository->shouldReceive('put')->once()->withArgs(fn ($key, $value, $ttl) => $value === 'short' && $ttl === 60)->andReturnTrue();

    $factory = Mockery::mock(Factory::class);
    $factory->shouldReceive('store')->with(null)->andReturn($repository);

    $manager = new TokenManager(app(Illuminate\Http\Client\Factory::class), $factory, config('naver-commerce'));

    expect($manager->token())->toBe('short');
});

it('sends account_id for SELLER tokens and caches them separately', function () {
    Http::fake($this->fakeToken('seller-token'));

    $manager = app(TokenManager::class);

    expect($manager->token('SELLER', 'seller-123'))->toBe('seller-token');
    expect($manager->cacheKey('SELLER', 'seller-123'))->not->toBe($manager->cacheKey('SELF', null));

    Http::assertSent(fn (Request $request) => $request['type'] === 'SELLER' && $request['account_id'] === 'seller-123');
});

it('requires account_id for SELLER tokens', function () {
    Http::fake();

    app(TokenManager::class)->token('SELLER');
})->throws(AuthenticationException::class);

it('forgets a cached token', function () {
    Http::fake($this->fakeToken());

    $manager = app(TokenManager::class);
    $manager->token();
    $manager->forget();
    $manager->token();

    Http::assertSentCount(2);
});

it('throws an AuthenticationException with error details when issuance fails', function () {
    Http::fake([
        $this->url('/v1/oauth2/token') => Http::response(['code' => 'GW.AUTHN', 'message' => '요청을 보낼 권한이 없습니다.', 'traceId' => 'trace-1'], 403),
    ]);

    try {
        app(TokenManager::class)->token();
        $this->fail('Expected exception');
    } catch (AuthenticationException $e) {
        expect($e->status())->toBe(403)
            ->and($e->errorCode())->toBe('GW.AUTHN')
            ->and($e->traceId())->toBe('trace-1')
            ->and($e->getMessage())->toContain('요청을 보낼 권한이 없습니다.');
    }
});
