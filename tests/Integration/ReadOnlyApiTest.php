<?php

use Sejongtf\LaravelNaverCommerce\Facades\NaverCommerce;
use Sejongtf\LaravelNaverCommerce\Tests\Integration\IntegrationTestCase;

/** @var IntegrationTestCase $this */
it('issues a real access token and caches it', function () {
    $first = NaverCommerce::token();
    $second = NaverCommerce::token();

    expect($first)->toBeString()->not->toBe('')
        ->and($second)->toBe($first);
});

it('fetches seller account info', function () {
    $account = NaverCommerce::seller()->account();

    expect($account)->toBeArray()->toHaveKeys(['accountId', 'accountUid']);
});

it('fetches seller channels', function () {
    $channels = NaverCommerce::seller()->channels();

    expect($channels)->toBeArray()->not->toBeEmpty()
        ->and($channels[0])->toHaveKeys(['channelNo', 'channelType', 'name']);
});

it('fetches leaf categories with a boolean query param', function () {
    $categories = NaverCommerce::categories()->all(true);

    expect($categories)->toBeArray()->not->toBeEmpty()
        ->and($categories[0]['last'])->toBeTrue();
});

it('fetches last changed product orders with a Carbon date-time param', function () {
    $result = NaverCommerce::orders()->lastChangedStatuses(now()->subHours(6));

    expect($result)->toBeArray()->toHaveKey('traceId')
        ->and(NaverCommerce::lastResponse()?->header('GNCP-GW-Trace-ID'))->not->toBeEmpty();
});

it('queries product orders with repeated array query keys', function () {
    $result = NaverCommerce::orders()->productOrders(now()->subDay(), [
        'productOrderStatuses' => ['PAYED', 'DELIVERING'],
        'pageSize' => 10,
    ]);

    expect($result)->toBeArray()->toHaveKey('data')
        ->and($result['data']['pagination']['size'] ?? null)->toBe(10);
});

it('searches products', function () {
    $result = NaverCommerce::products()->search(['page' => 1, 'size' => 5]);

    expect($result)->toBeArray()->toHaveKeys(['contents', 'totalElements']);
});
