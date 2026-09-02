<?php

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

/** @var TestCase $this */
const CHANGED_URL = TestCase::BASE.'/v1/pay-order/seller/product-orders/last-changed-statuses';

function changedItem(string $id, string $status = 'PAYED'): array
{
    return [
        'productOrderId' => $id,
        'orderId' => 'O-'.$id,
        'productOrderStatus' => $status,
        'lastChangedType' => $status,
        'lastChangedDate' => '2026-09-02T10:00:00.000+09:00',
    ];
}

beforeEach(fn () => CarbonImmutable::setTestNow('2026-09-02 12:00:00 Asia/Seoul'));
afterEach(fn () => CarbonImmutable::setTestNow());

it('lists changed orders in a table for a relative --since', function () {
    $this->fakeApi([
        CHANGED_URL.'*' => Http::response(['data' => ['count' => 1, 'lastChangeStatuses' => [changedItem('P1')]]]),
    ]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed', ['--since' => '6h', '--type' => 'PAYED', '--limit' => 50]);

    expect($code)->toBe(0)
        ->and($output)->toContain('P1')->toContain('O-P1')->toContain('PAYED')->toContain('Rows')->toContain(' 1');

    $this->assertApiSent(fn (Request $request) => $request->url() === CHANGED_URL
        .'?lastChangedFrom=2026-09-02T06%3A00%3A00.000%2B09%3A00'
        .'&lastChangedType=PAYED&limitCount=50'
        .'&lastChangedTo=2026-09-02T12%3A00%3A00.000%2B09%3A00');
});

it('accepts explicit --since and --until date-times and prints JSON', function () {
    $this->fakeApi([
        CHANGED_URL.'*' => Http::response(['data' => ['count' => 1, 'lastChangeStatuses' => [changedItem('P2', 'DISPATCHED')]]]),
    ]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed', [
        '--since' => '2026-09-01 00:00:00', '--until' => '2026-09-01 12:00:00', '--json' => true,
    ]);

    expect($code)->toBe(0)
        ->and(json_decode($output, true))->toBe([changedItem('P2', 'DISPATCHED')]);

    $this->assertApiSent(fn (Request $request) => str_contains($request->url(), 'lastChangedFrom=2026-09-01T00%3A00%3A00.000%2B09%3A00')
        && str_contains($request->url(), 'lastChangedTo=2026-09-01T12%3A00%3A00.000%2B09%3A00'));
});

it('follows more pagination with --all', function () {
    $page = 0;
    $this->fakeApi([
        CHANGED_URL.'*' => function () use (&$page) {
            $page++;

            return $page === 1
                ? Http::response(['data' => [
                    'count' => 1,
                    'lastChangeStatuses' => [changedItem('P1')],
                    'more' => ['moreFrom' => '2026-09-02T11:00:00.000+09:00', 'moreSequence' => '77'],
                ]])
                : Http::response(['data' => ['count' => 1, 'lastChangeStatuses' => [changedItem('P2')]]]);
        },
    ]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed', ['--all' => true, '--json' => true]);

    expect($code)->toBe(0)
        ->and(array_column(json_decode($output, true), 'productOrderId'))->toBe(['P1', 'P2']);

    $this->assertApiSent(fn (Request $request) => str_contains($request->url(), 'lastChangedFrom=2026-09-02T11%3A00%3A00.000%2B09%3A00')
        && str_contains($request->url(), 'moreSequence=77'));
});

it('splits windows longer than 24 hours into 24-hour chunks', function () {
    $urls = [];
    $this->fakeApi([
        CHANGED_URL.'*' => function (Request $request) use (&$urls) {
            $urls[] = urldecode($request->url());

            return Http::response(['data' => ['count' => 1, 'lastChangeStatuses' => [changedItem('P'.count($urls))]]]);
        },
    ]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed', ['--since' => '60h', '--json' => true]);

    expect($code)->toBe(0)
        ->and(array_column(json_decode($output, true), 'productOrderId'))->toBe(['P1', 'P2', 'P3'])
        ->and($urls)->toHaveCount(3)
        ->and($urls[0])->toContain('lastChangedFrom=2026-08-31T00:00:00.000+09:00', 'lastChangedTo=2026-09-01T00:00:00.000+09:00')
        ->and($urls[1])->toContain('lastChangedFrom=2026-09-01T00:00:00.000+09:00', 'lastChangedTo=2026-09-02T00:00:00.000+09:00')
        ->and($urls[2])->toContain('lastChangedFrom=2026-09-02T00:00:00.000+09:00', 'lastChangedTo=2026-09-02T12:00:00.000+09:00');
});

it('reports the request cap, not --all, when a long window exhausts the cap without --all', function () {
    $this->fakeApi([
        CHANGED_URL.'*' => Http::response(['data' => ['count' => 0, 'lastChangeStatuses' => []]]),
    ]);

    // 250 days → 250 daily chunks, above the 200-request cap.
    [$code, $output] = runArtisan('naver-commerce:orders:changed', ['--since' => '250d']);

    expect($code)->toBe(1)
        ->and($output)->toContain('Stopped after 200 requests')
        ->not->toContain('pass --all');
    Http::assertSentCount(201); // token + 200 pages
});

it('rejects --until earlier than --since', function () {
    [$code, $output] = runArtisan('naver-commerce:orders:changed', ['--since' => '1h', '--until' => '2020-01-01']);

    expect($code)->toBe(2)
        ->and($output)->toContain('--until must be later');
});

it('warns about more rows without --all', function () {
    $this->fakeApi([
        CHANGED_URL.'*' => Http::response(['data' => [
            'count' => 1,
            'lastChangeStatuses' => [changedItem('P1')],
            'more' => ['moreFrom' => '2026-09-02T11:00:00.000+09:00', 'moreSequence' => '1'],
        ]]),
    ]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed');

    expect($code)->toBe(1)
        ->and($output)->toContain('P1')->toContain('--all');
    Http::assertSentCount(2); // token + one page
});

it('exits non-zero in --json mode when the result is truncated', function () {
    $this->fakeApi([
        CHANGED_URL.'*' => Http::response(['data' => [
            'count' => 1,
            'lastChangeStatuses' => [changedItem('P1')],
            'more' => ['moreFrom' => '2026-09-02T11:00:00.000+09:00', 'moreSequence' => '1'],
        ]]),
    ]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed', ['--json' => true]);

    // BufferedOutput has no separate stderr, so the warning lands in the same buffer here.
    expect($code)->toBe(1)
        ->and($output)->toContain('"productOrderId": "P1"')->toContain('--all');
});

it('reports an empty window', function () {
    $this->fakeApi([CHANGED_URL.'*' => Http::response(['data' => ['count' => 0, 'lastChangeStatuses' => []]])]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed');

    expect($code)->toBe(0)
        ->and($output)->toContain('No changed product orders');
});

it('rejects an unparseable --since', function () {
    [$code, $output] = runArtisan('naver-commerce:orders:changed', ['--since' => 'yesterday-ish??']);

    expect($code)->toBe(2)
        ->and($output)->toContain('Cannot parse --since');
});

it('fails with the API error', function () {
    $this->fakeApi([CHANGED_URL.'*' => Http::response(['code' => 'BAD_REQUEST', 'message' => 'bad range'], 400)]);

    [$code, $output] = runArtisan('naver-commerce:orders:changed');

    expect($code)->toBe(1)
        ->and($output)->toContain('BAD_REQUEST');
});
