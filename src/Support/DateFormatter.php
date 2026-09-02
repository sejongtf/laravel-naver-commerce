<?php

namespace Sejongtf\LaravelNaverCommerce\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * 커머스API 날짜/시간 규격(KST, ISO 8601) 포맷터.
 */
class DateFormatter
{
    public const TIMEZONE = 'Asia/Seoul';

    /**
     * yyyy-MM-dd'T'HH:mm:ss.SSSXXX (예: 2022-01-01T01:01:01.001+09:00)
     */
    public static function dateTime(DateTimeInterface $date): string
    {
        return static::toKst($date)->format('Y-m-d\TH:i:s.vP');
    }

    /**
     * yyyy-MM-dd (KST 기준)
     */
    public static function date(DateTimeInterface $date): string
    {
        return static::toKst($date)->format('Y-m-d');
    }

    /**
     * yyyy-MM (KST 기준)
     */
    public static function month(DateTimeInterface $date): string
    {
        return static::toKst($date)->format('Y-m');
    }

    protected static function toKst(DateTimeInterface $date): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($date)->setTimezone(new DateTimeZone(self::TIMEZONE));
    }
}
