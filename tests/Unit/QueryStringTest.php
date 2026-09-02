<?php

use Sejongtf\LaravelNaverCommerce\Http\QueryString;

enum Status: string
{
    case Payed = 'PAYED';
}

it('serializes arrays as repeated keys', function () {
    expect(QueryString::build(['productOrderStatuses' => ['PAYED', 'DELIVERING'], 'page' => 1]))
        ->toBe('productOrderStatuses=PAYED&productOrderStatuses=DELIVERING&page=1');
});

it('converts booleans, dates, enums and skips nulls', function () {
    $from = new DateTimeImmutable('2024-03-01 00:00:00', new DateTimeZone('Asia/Seoul'));

    expect(QueryString::build(['fulfillment' => false, 'last' => true, 'from' => $from, 'to' => null, 'status' => Status::Payed]))
        ->toBe('fulfillment=false&last=true&from=2024-03-01T00%3A00%3A00.000%2B09%3A00&status=PAYED');
});

it('returns an empty string for empty params', function () {
    expect(QueryString::build([]))->toBe('');
    expect(QueryString::build(['a' => null]))->toBe('');
});

it('normalizes nested body values', function () {
    $date = new DateTimeImmutable('2024-03-01 09:00:00', new DateTimeZone('Asia/Seoul'));

    $body = QueryString::normalizeBody([
        'dispatchProductOrders' => [
            ['productOrderId' => '1', 'dispatchDate' => $date, 'status' => Status::Payed],
        ],
        'flag' => true,
    ]);

    expect($body['dispatchProductOrders'][0]['dispatchDate'])->toBe('2024-03-01T09:00:00.000+09:00');
    expect($body['dispatchProductOrders'][0]['status'])->toBe('PAYED');
    expect($body['flag'])->toBeTrue();
});
