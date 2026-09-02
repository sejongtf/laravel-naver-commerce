<?php

namespace Sejongtf\LaravelNaverCommerce\Http;

use BackedEnum;
use DateTimeInterface;
use Sejongtf\LaravelNaverCommerce\Support\DateFormatter;

/**
 * 커머스API(Spring) 규격의 쿼리 문자열 빌더.
 *
 * - 배열은 반복 키(a=1&a=2)로 직렬화한다(http_build_query의 a[0]=1 형식은 지원되지 않음).
 * - bool → "true"/"false", DateTimeInterface → KST ISO 8601, null 은 제외한다.
 */
class QueryString
{
    public static function build(array $params): string
    {
        $pairs = [];

        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null) {
                        continue;
                    }
                    $pairs[] = rawurlencode((string) $key).'='.rawurlencode(static::scalar($item));
                }

                continue;
            }

            $pairs[] = rawurlencode((string) $key).'='.rawurlencode(static::scalar($value));
        }

        return implode('&', $pairs);
    }

    /**
     * 스칼라 값을 API 규격 문자열로 변환한다.
     */
    public static function scalar(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value instanceof DateTimeInterface => DateFormatter::dateTime($value),
            $value instanceof BackedEnum => (string) $value->value,
            default => (string) $value,
        };
    }

    /**
     * JSON 본문 내 값을 재귀적으로 정규화한다(DateTimeInterface, BackedEnum 변환).
     */
    public static function normalizeBody(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::normalizeBody($value);
            } elseif ($value instanceof DateTimeInterface) {
                $data[$key] = DateFormatter::dateTime($value);
            } elseif ($value instanceof BackedEnum) {
                $data[$key] = $value->value;
            }
        }

        return $data;
    }
}
