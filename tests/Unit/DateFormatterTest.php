<?php

use Carbon\Carbon;
use Sejongtf\LaravelNaverCommerce\Support\DateFormatter;

it('formats date-time as KST ISO 8601 with milliseconds', function () {
    $utc = new DateTimeImmutable('2022-01-01 00:00:00.123', new DateTimeZone('UTC'));

    expect(DateFormatter::dateTime($utc))->toBe('2022-01-01T09:00:00.123+09:00');
});

it('formats date and month in KST', function () {
    $utc = new DateTimeImmutable('2022-07-24 20:30:00', new DateTimeZone('UTC'));

    expect(DateFormatter::date($utc))->toBe('2022-07-25');
    expect(DateFormatter::month($utc))->toBe('2022-07');
});

it('accepts mutable DateTime and Carbon instances', function () {
    $mutable = new DateTime('2023-07-25 10:10:10.100', new DateTimeZone('Asia/Seoul'));

    expect(DateFormatter::dateTime($mutable))->toBe('2023-07-25T10:10:10.100+09:00');
    expect(DateFormatter::dateTime(Carbon::parse('2023-07-25T01:10:10Z')))->toBe('2023-07-25T10:10:10.000+09:00');
});
