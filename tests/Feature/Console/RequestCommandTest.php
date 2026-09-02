<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

/** @var TestCase $this */

it('performs a GET with repeated query keys and prints pretty JSON', function () {
    $this->fakeApi([
        $this->url('/v1/pay-order/seller/product-orders*') => Http::response(['data' => ['count' => 2]]),
    ]);

    [$code, $output] = runArtisan('naver-commerce:request', [
        'method' => 'get',
        'path' => '/v1/pay-order/seller/product-orders',
        '--query' => ['from=2026-01-01T00:00:00.000+09:00', 'productOrderStatuses=PAYED', 'productOrderStatuses=DELIVERING', 'pageSize=10'],
    ]);

    expect($code)->toBe(0)
        ->and($output)->toContain('"count": 2');

    $this->assertApiSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === $this->url('/v1/pay-order/seller/product-orders')
            .'?from=2026-01-01T00%3A00%3A00.000%2B09%3A00&productOrderStatuses=PAYED&productOrderStatuses=DELIVERING&pageSize=10');
});

it('refuses a non-GET request without --force when not confirmed', function () {
    $this->fakeApi();

    [$code, $output] = runArtisan('naver-commerce:request', [
        'method' => 'POST',
        'path' => '/v1/products/search',
        '--json' => '{"page":1}',
        '--no-interaction' => true,
    ]);

    expect($code)->toBe(1)
        ->and($output)->toContain('--force');

    Http::assertNothingSent();
});

it('sends a non-GET request when the prompt is confirmed', function () {
    $this->fakeApi([
        $this->url('/v1/products/search') => Http::response(['contents' => []]),
    ]);

    $this->artisan('naver-commerce:request', ['method' => 'POST', 'path' => '/v1/products/search', '--json' => '{"page":1}'])
        ->expectsConfirmation('POST /v1/products/search may modify seller data. Continue?', 'yes')
        ->assertSuccessful();

    $this->assertApiSent(fn (Request $request) => $request->method() === 'POST' && $request['page'] === 1);
});

it('sends a JSON body from a file with --force', function () {
    $file = tempnam(sys_get_temp_dir(), 'nc');
    file_put_contents($file, '{"productOrderIds":["A","B"]}');

    $this->fakeApi([
        $this->url('/v1/pay-order/seller/product-orders/query') => Http::response(['data' => []]),
    ]);

    [$code] = runArtisan('naver-commerce:request', [
        'method' => 'POST',
        'path' => '/v1/pay-order/seller/product-orders/query',
        '--json' => '@'.$file,
        '--force' => true,
    ]);

    unlink($file);

    expect($code)->toBe(0);
    $this->assertApiSent(fn (Request $request) => $request['productOrderIds'] === ['A', 'B']);
});

it('rejects an invalid JSON body', function () {
    $this->fakeApi();

    [$code, $output] = runArtisan('naver-commerce:request', [
        'method' => 'POST', 'path' => '/x', '--json' => '{oops', '--force' => true,
    ]);

    expect($code)->toBe(2)
        ->and($output)->toContain('Invalid JSON body');
    Http::assertNothingSent();
});

it('rejects an unsupported HTTP method', function () {
    [$code, $output] = runArtisan('naver-commerce:request', ['method' => 'HEAD', 'path' => '/x']);

    expect($code)->toBe(2)
        ->and($output)->toContain('Unsupported HTTP method');
});

it('uses a SELLER token with --seller', function () {
    $this->fakeApi([$this->url('/v1/seller/account') => Http::response(['accountId' => 'a'])]);

    [$code] = runArtisan('naver-commerce:request', ['method' => 'GET', 'path' => '/v1/seller/account', '--seller' => 'ncp_seller_01']);

    expect($code)->toBe(0);
    assertSellerTokenRequested('ncp_seller_01');
});

it('prints the API error body on failure', function () {
    $this->fakeApi([
        $this->url('/v1/seller/account') => Http::response([
            'code' => 'BAD_REQUEST', 'message' => 'nope', 'traceId' => 'trace-9', 'invalidInputs' => [['name' => 'x']],
        ], 400),
    ]);

    [$code, $output] = runArtisan('naver-commerce:request', ['method' => 'GET', 'path' => '/v1/seller/account']);

    expect($code)->toBe(1)
        ->and($output)->toContain('BAD_REQUEST')->toContain('trace-9')->toContain('invalidInputs');
});
