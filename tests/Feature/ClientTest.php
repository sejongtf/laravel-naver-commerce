<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelNaverCommerce\Exceptions\ApiException;
use Sejongtf\LaravelNaverCommerce\Exceptions\AuthenticationException;
use Sejongtf\LaravelNaverCommerce\Exceptions\RateLimitException;
use Sejongtf\LaravelNaverCommerce\Facades\NaverCommerce;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

/** @var TestCase $this */

it('sends bearer token and json accept header', function () {
    $this->fakeApi([$this->url('/v1/seller/account') => Http::response(['data' => ['id' => 1]])]);

    $result = NaverCommerce::seller()->account();

    expect($result)->toBe(['data' => ['id' => 1]]);

    $this->assertApiSent(fn (Request $r) => $r->url() === $this->url('/v1/seller/account')
        && $r->hasHeader('Authorization', 'Bearer test-token')
        && $r->hasHeader('Accept', 'application/json'));
});

it('builds spring-style repeated query keys and formats dates', function () {
    $this->fakeApi([$this->url('/v1/pay-order/seller/product-orders*') => Http::response(['data' => []])]);

    $from = new DateTimeImmutable('2024-05-01 00:00:00', new DateTimeZone('Asia/Seoul'));

    NaverCommerce::orders()->productOrders($from, [
        'productOrderStatuses' => ['PAYED', 'DELIVERING'],
        'fulfillment' => false,
        'page' => 2,
        'to' => null,
    ]);

    $this->assertApiSent(fn (Request $r) => $r->url() === $this->url('/v1/pay-order/seller/product-orders')
        .'?from=2024-05-01T00%3A00%3A00.000%2B09%3A00&productOrderStatuses=PAYED&productOrderStatuses=DELIVERING&fulfillment=false&page=2');
});

it('re-issues the token and retries once on 401 GW.AUTHN', function () {
    Http::fake([
        $this->url('/v1/oauth2/token') => Http::sequence()
            ->push(['access_token' => 'expired', 'expires_in' => 3600])
            ->push(['access_token' => 'fresh', 'expires_in' => 3600]),
        $this->url('/v1/seller/channels') => Http::sequence()
            ->push(['code' => 'GW.AUTHN', 'message' => '요청을 보낼 권한이 없습니다.'], 401)
            ->push(['data' => ['ok' => true]]),
    ]);

    $result = NaverCommerce::seller()->channels();

    expect($result['data']['ok'])->toBeTrue();

    Http::assertSentCount(4);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v1/seller/channels') && $r->hasHeader('Authorization', 'Bearer fresh'));
});

it('does not retry a second 401 and throws AuthenticationException', function () {
    Http::fake([
        $this->url('/v1/oauth2/token') => Http::response(['access_token' => 't', 'expires_in' => 3600]),
        $this->url('/v1/seller/channels') => Http::response(['code' => 'GW.AUTHN', 'message' => 'nope', 'traceId' => 'tr'], 401),
    ]);

    try {
        NaverCommerce::seller()->channels();
        $this->fail('Expected exception');
    } catch (AuthenticationException $e) {
        expect($e->isTokenExpired())->toBeTrue()
            ->and($e->isGatewayError())->toBeTrue()
            ->and($e->traceId())->toBe('tr');
    }

    Http::assertSentCount(4); // token, 401, token, 401
});

it('throws RateLimitException with header accessors on 429', function () {
    $this->fakeApi([
        $this->url('/v1/seller/account') => Http::response(
            ['code' => 'GW.RATE_LIMIT', 'message' => '요청이 많아 서비스를 일시적으로 사용할 수 없습니다.'],
            429,
            ['GNCP-GW-RateLimit-Replenish-Rate' => '5', 'GNCP-GW-RateLimit-Burst-Capacity' => '10', 'GNCP-GW-RateLimit-Remaining' => '0', 'GNCP-GW-Trace-ID' => 'trace-429'],
        ),
    ]);

    try {
        NaverCommerce::seller()->account();
        $this->fail('Expected exception');
    } catch (RateLimitException $e) {
        expect($e->status())->toBe(429)
            ->and($e->errorCode())->toBe('GW.RATE_LIMIT')
            ->and($e->isQuotaLimit())->toBeFalse()
            ->and($e->replenishRate())->toBe(5)
            ->and($e->burstCapacity())->toBe(10)
            ->and($e->remaining())->toBe(0)
            ->and($e->traceId())->toBe('trace-429');
    }
});

it('throws ApiException for 4xx responses with body access', function () {
    $this->fakeApi([
        $this->url('/v2/products') => Http::response(['code' => 'BAD_REQUEST', 'message' => '유효성 검사 오류', 'invalidInputs' => [['name' => 'originProduct.name']]], 400),
    ]);

    try {
        NaverCommerce::products()->create(['originProduct' => []]);
        $this->fail('Expected exception');
    } catch (ApiException $e) {
        expect($e->status())->toBe(400)
            ->and($e->errorCode())->toBe('BAD_REQUEST')
            ->and($e->isGatewayError())->toBeFalse()
            ->and($e->errors()['invalidInputs'][0]['name'])->toBe('originProduct.name')
            ->and($e->getMessage())->toBe('[400 BAD_REQUEST] 유효성 검사 오류');
    }
});

it('retries server errors according to config', function () {
    config()->set('naver-commerce.retry.times', 2);

    Http::fake($this->fakeToken() + [
        $this->url('/v1/seller/account') => Http::sequence()
            ->push(['code' => 'GW.PROXY.05'], 500)
            ->push(['code' => 'GW.BLOCK.01'], 503)
            ->push(['data' => 'ok']),
    ]);

    expect(NaverCommerce::seller()->account())->toBe(['data' => 'ok']);
    Http::assertSentCount(4);
});

it('does not retry server errors when retry is disabled', function () {
    $this->fakeApi([$this->url('/v1/seller/account') => Http::response(['code' => 'GW.INTERNAL_SERVER_ERROR'], 500)]);

    expect(fn () => NaverCommerce::seller()->account())->toThrow(ApiException::class);
    Http::assertSentCount(2);
});

it('returns an empty array for 204 responses', function () {
    $this->fakeApi([$this->url('/v2/products/origin-products/123') => Http::response(null, 204)]);

    expect(NaverCommerce::products()->deleteOrigin(123))->toBe([]);
    $this->assertApiSent(fn (Request $r) => $r->method() === 'DELETE');
});

it('uses SELLER tokens via forSeller without mutating the default instance', function () {
    Http::fake([
        $this->url('/v1/oauth2/token') => Http::sequence()
            ->push(['access_token' => 'seller-tok', 'expires_in' => 3600])
            ->push(['access_token' => 'self-tok', 'expires_in' => 3600]),
        $this->url('/v1/seller/account') => Http::response(['data' => 1]),
    ]);

    NaverCommerce::forSeller('acc-1')->seller()->account();
    NaverCommerce::seller()->account();

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'oauth2/token') && $r['type'] === 'SELLER' && $r['account_id'] === 'acc-1');
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'oauth2/token') && $r['type'] === 'SELF');
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v1/seller/account') && $r->hasHeader('Authorization', 'Bearer seller-tok'));
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v1/seller/account') && $r->hasHeader('Authorization', 'Bearer self-tok'));

    expect(NaverCommerce::client()->tokenType())->toBe('SELF');
});

it('exposes lastResponse for header inspection', function () {
    $this->fakeApi([$this->url('/v1/seller/account') => Http::response(['data' => 1], 200, ['GNCP-GW-Trace-ID' => 'abc'])]);

    NaverCommerce::seller()->account();

    expect(NaverCommerce::lastResponse()->header('GNCP-GW-Trace-ID'))->toBe('abc');
});

it('supports generic requests for unmapped endpoints', function () {
    $this->fakeApi([$this->url('/v9/future*') => Http::response(['ok' => true])]);

    expect(NaverCommerce::post('/v9/future', ['a' => 1], ['q' => 'x']))->toBe(['ok' => true]);

    $this->assertApiSent(fn (Request $r) => $r->url() === $this->url('/v9/future?q=x') && $r->method() === 'POST' && $r['a'] === 1);
});
