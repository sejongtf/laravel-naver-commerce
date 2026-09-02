<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelNaverCommerce\Auth\TokenManager;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

/** @var TestCase $this */

function assertSellerTokenRequested(string $accountId): void
{
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), TokenManager::TOKEN_PATH)
        && $request['type'] === 'SELLER'
        && $request['account_id'] === $accountId);
}

it('issues a token and prints it masked', function () {
    Http::fake($this->fakeToken('abcdef-secret-middle-uvwxyz'));
    $key = app(TokenManager::class)->cacheKey(TokenManager::TYPE_SELF, null);

    [$code, $output] = runArtisan('naver-commerce:token');

    expect($code)->toBe(0)
        ->and($output)->toContain('SELF')
        ->toContain($key)
        ->toContain('abcdef********uvwxyz')
        ->toContain('--show')
        ->not->toContain('abcdef-secret-middle-uvwxyz');
});

it('prints the full token with --show', function () {
    Http::fake($this->fakeToken('full-visible-token-value'));

    [$code, $output] = runArtisan('naver-commerce:token', ['--show' => true]);

    expect($code)->toBe(0)
        ->and($output)->toContain('full-visible-token-value')
        ->not->toContain('--show');
});

it('issues a SELLER token with --seller', function () {
    $this->fakeApi();

    [$code, $output] = runArtisan('naver-commerce:token', ['--seller' => 'ncp_seller_01']);

    expect($code)->toBe(0)
        ->and($output)->toContain('SELLER')->toContain('ncp_seller_01');

    assertSellerTokenRequested('ncp_seller_01');
});

it('reissues the token with --fresh', function () {
    $this->fakeApi();
    app(TokenManager::class)->token();
    Http::assertSentCount(1);

    [$code] = runArtisan('naver-commerce:token', ['--fresh' => true]);

    expect($code)->toBe(0);
    Http::assertSentCount(2);
});

it('reuses the cached token without --fresh', function () {
    $this->fakeApi();
    app(TokenManager::class)->token();

    runArtisan('naver-commerce:token');

    Http::assertSentCount(1);
});

it('fails with the API error when token issuance fails', function () {
    Http::fake([
        TestCase::BASE.TokenManager::TOKEN_PATH => Http::response([
            'code' => 'GW.AUTHN',
            'message' => 'invalid signature',
            'traceId' => 'trace-1',
        ], 401),
    ]);

    [$code, $output] = runArtisan('naver-commerce:token');

    expect($code)->toBe(1)
        ->and($output)->toContain('GW.AUTHN')->toContain('invalid signature');
});

it('forgets the cached token', function () {
    $this->fakeApi();
    $tokens = app(TokenManager::class);
    $key = $tokens->cacheKey(TokenManager::TYPE_SELF, null);

    $tokens->token();
    expect(Cache::get($key))->toBe('test-token');

    [$code, $output] = runArtisan('naver-commerce:token:forget');

    expect($code)->toBe(0)
        ->and($output)->toContain($key)
        ->and(Cache::get($key))->toBeNull();
});

it('forgets a SELLER token with --seller', function () {
    $key = app(TokenManager::class)->cacheKey(TokenManager::TYPE_SELLER, 'ncp_seller_01');
    Cache::put($key, 'seller-token', 600);

    [$code] = runArtisan('naver-commerce:token:forget', ['--seller' => 'ncp_seller_01']);

    expect($code)->toBe(0)
        ->and(Cache::get($key))->toBeNull();
});

it('pings the API and prints account and channels', function () {
    $this->fakeApi([
        $this->url('/v1/seller/account') => Http::response(['accountId' => 'ncp_test_01', 'accountUid' => 'uid-123']),
        $this->url('/v1/seller/channels') => Http::response(
            [['channelNo' => 100, 'channelType' => 'STOREFARM', 'name' => 'My Store']],
            200,
            ['GNCP-GW-Trace-ID' => 'trace-xyz', 'GNCP-GW-RateLimit-Remaining' => '9', 'GNCP-GW-Quota-Remaining' => '999'],
        ),
    ]);

    [$code, $output] = runArtisan('naver-commerce:ping');

    expect($code)->toBe(0)
        ->and($output)->toContain('ncp_test_01')
        ->toContain('uid-123')
        ->toContain('Channel #100 (STOREFARM)')
        ->toContain('My Store')
        ->toContain('trace-xyz')
        ->toContain('reachable');
});

it('pings with a SELLER token when --seller is given', function () {
    $this->fakeApi([
        $this->url('/v1/seller/account') => Http::response(['accountId' => 'a', 'accountUid' => 'b']),
        $this->url('/v1/seller/channels') => Http::response([]),
    ]);

    [$code] = runArtisan('naver-commerce:ping', ['--seller' => 'ncp_seller_01']);

    expect($code)->toBe(0);
    assertSellerTokenRequested('ncp_seller_01');
});

it('fails the ping with the API error and trace ID', function () {
    $this->fakeApi([
        $this->url('/v1/seller/account') => Http::response([
            'code' => 'GW.AUTHZ',
            'message' => 'forbidden',
            'traceId' => 'trace-err',
        ], 403),
    ]);

    [$code, $output] = runArtisan('naver-commerce:ping');

    expect($code)->toBe(1)
        ->and($output)->toContain('GW.AUTHZ')->toContain('trace-err');
});
