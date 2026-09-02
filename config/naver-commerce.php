<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 애플리케이션 자격증명
    |--------------------------------------------------------------------------
    |
    | 커머스API센터에서 발급받은 애플리케이션 ID / 시크릿.
    | 시크릿은 bcrypt salt 형식($2a$10$...)이며 전자서명 생성에만 사용되고
    | 네트워크로 직접 전송되지 않습니다.
    |
    */

    'client_id' => env('NAVER_COMMERCE_CLIENT_ID'),
    'client_secret' => env('NAVER_COMMERCE_CLIENT_SECRET'),

    'base_url' => env('NAVER_COMMERCE_BASE_URL', 'https://api.commerce.naver.com/external'),

    'timeout' => (int) env('NAVER_COMMERCE_TIMEOUT', 30),
    'connect_timeout' => (int) env('NAVER_COMMERCE_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | 인증 토큰
    |--------------------------------------------------------------------------
    |
    | type: SELF(자기 리소스) 또는 SELLER(위임받은 판매자 리소스, account_id 필요).
    | 토큰은 expires_in - ttl_margin 초 동안 캐시됩니다.
    |
    */

    'token' => [
        'type' => env('NAVER_COMMERCE_TOKEN_TYPE', 'SELF'),
        'account_id' => env('NAVER_COMMERCE_ACCOUNT_ID'),
        'cache_store' => env('NAVER_COMMERCE_CACHE_STORE'),
        'cache_prefix' => 'naver-commerce:token',
        'ttl_margin' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | 재시도
    |--------------------------------------------------------------------------
    |
    | 5xx 응답 및 연결 오류에 대한 재시도 횟수와 대기 시간(ms).
    | 401 GW.AUTHN(토큰 만료)에 대한 토큰 재발급 후 1회 재시도는 이 설정과
    | 무관하게 항상 수행됩니다.
    |
    */

    'retry' => [
        'times' => (int) env('NAVER_COMMERCE_RETRY_TIMES', 0),
        'sleep_ms' => (int) env('NAVER_COMMERCE_RETRY_SLEEP_MS', 500),
    ],

];
