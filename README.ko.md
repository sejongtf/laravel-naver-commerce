# laravel-naver-commerce

[![Tests](https://github.com/sejongtf/laravel-naver-commerce/actions/workflows/tests.yml/badge.svg?branch=0.x)](https://github.com/sejongtf/laravel-naver-commerce/actions/workflows/tests.yml)

[English](README.md)

네이버 커머스API(스마트스토어)를 위한 Laravel 클라이언트 패키지.
[커머스API 센터](https://apicenter.commerce.naver.com/docs/introduction)의 전 도메인(인증·주문·상품·정산·문의·물류·판매자정보·커머스솔루션) 116개 엔드포인트를 도메인별 리소스 클래스로 제공합니다.

- OAuth 2.0 Client Credentials 토큰 자동 발급·캐시·재발급(401 `GW.AUTHN` 시 1회 자동 재시도)
- bcrypt 전자서명(`client_secret_sign`) 생성
- Spring 규격 쿼리 문자열(배열 → 반복 키), KST ISO 8601 날짜 자동 변환
- 게이트웨이/API 오류를 상태별 예외로 변환(`traceId`, 요청량 헤더 접근 가능)
- `SELF`/`SELLER` 토큰 타입 지원(`forSeller()`)

## 요구 사항

- PHP 8.4+
- Laravel 13

## 설치

```bash
composer require sejongtf/laravel-naver-commerce
php artisan vendor:publish --tag=naver-commerce-config   # 선택
```

`.env`:

```dotenv
NAVER_COMMERCE_CLIENT_ID=your-application-id
NAVER_COMMERCE_CLIENT_SECRET='$2a$10$...'   # 애플리케이션 시크릿(bcrypt salt 형식). $ 때문에 반드시 따옴표로 감싸세요.
```

선택 설정:

| 키 | 기본값 | 설명 |
|---|---|---|
| `NAVER_COMMERCE_TOKEN_TYPE` | `SELF` | 기본 토큰 타입(`SELF` / `SELLER`) |
| `NAVER_COMMERCE_ACCOUNT_ID` | – | 기본 타입이 `SELLER`일 때 판매자 ID |
| `NAVER_COMMERCE_CACHE_STORE` | 기본 캐시 | 토큰 캐시 스토어 |
| `NAVER_COMMERCE_TIMEOUT` | `30` | 응답 타임아웃(초) |
| `NAVER_COMMERCE_RETRY_TIMES` | `0` | 5xx/연결 오류 재시도 횟수 |

## 사용법

```php
use Sejongtf\LaravelNaverCommerce\Facades\NaverCommerce;

// 판매자 계정 정보
$account = NaverCommerce::seller()->account();

// 최근 1시간 변경 주문 폴링
$changed = NaverCommerce::orders()->lastChangedStatuses(now()->subHour());
$ids = collect($changed['data']['lastChangeStatuses'] ?? [])->pluck('productOrderId');

// 상품 주문 상세(다건)
$orders = NaverCommerce::orders()->query($ids->all());

// 발주 확인 → 발송 처리
NaverCommerce::orders()->confirm(['2024010112345']);
NaverCommerce::orders()->dispatch([
    ['productOrderId' => '2024010112345', 'deliveryMethod' => 'DELIVERY', 'deliveryCompanyCode' => 'CJGLS', 'trackingNumber' => '1234567890'],
]);

// 조건형 주문 조회 — 배열은 반복 키(productOrderStatuses=PAYED&productOrderStatuses=DELIVERING)로 전송
$paid = NaverCommerce::orders()->productOrders(now()->subDay(), [
    'productOrderStatuses' => ['PAYED', 'DELIVERING'],
    'pageSize' => 300,
]);

// 상품 이미지 업로드 후 상품 등록
$images = NaverCommerce::products()->uploadImages([storage_path('app/main.jpg')]);
NaverCommerce::products()->create([
    'originProduct' => [
        'statusType' => 'SALE',
        'name' => '상품명',
        'salePrice' => 10000,
        'images' => ['representativeImage' => ['url' => $images['images'][0]['url']]],
        // ...
    ],
    'smartstoreChannelProduct' => ['naverShoppingRegistration' => true, 'channelProductDisplayStatusType' => 'ON'],
]);
```

모든 메서드는 응답 JSON을 `array`로 반환합니다. `DateTimeInterface`(Carbon 포함) 값은 쿼리·본문 어디에 있어도 `2024-01-01T00:00:00.000+09:00` 형식으로 변환됩니다. `yyyy-MM-dd` 형식이 필요한 파라미터(정산 등)는 문자열로 넘기거나 `DateFormatter::date()`를 사용하세요.

### 판매자 대리 호출(SELLER 토큰)

```php
NaverCommerce::forSeller('seller-account-id')->orders()->productOrders(now()->subDay());
```

토큰은 `{type}:{accountId}` 단위로 별도 캐시됩니다.

### 예외 처리

| 예외 | 조건 |
|---|---|
| `AuthenticationException` | 401, 토큰 발급 실패, 자격증명 오류. `isTokenExpired()` |
| `RateLimitException` | 429 (`GW.RATE_LIMIT`, `GW.QUOTA_LIMIT`). `replenishRate()`, `remaining()`, `quotaRemaining()` 등 |
| `ApiException` | 그 외 4xx/5xx, 연결 오류 |

모두 `NaverCommerceException`을 상속하며 `status()`, `errorCode()`, `traceId()`, `errors()`(응답 본문), `response()`를 제공합니다.

```php
use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;

try {
    NaverCommerce::products()->create($payload);
} catch (NaverCommerceException $e) {
    Log::warning('naver commerce failed', [
        'status' => $e->status(), 'code' => $e->errorCode(), 'traceId' => $e->traceId(), 'body' => $e->errors(),
    ]);
}
```

마지막 응답 헤더(요청량 등)는 `NaverCommerce::lastResponse()?->header('GNCP-GW-RateLimit-Remaining')`로 확인할 수 있습니다.

### 미지원·신규 엔드포인트

```php
NaverCommerce::get('/v1/some/new-endpoint', ['page' => 1]);
NaverCommerce::post('/v1/some/new-endpoint', ['body' => 'x'], ['query' => 'y']);
NaverCommerce::request('PUT', '/v1/...', ['json' => [...], 'query' => [...]]); // Illuminate Response 반환
```

### Artisan 커맨드

```bash
php artisan naver-commerce:ping                     # 판매자 계정·채널을 조회해 자격증명을 확인
php artisan naver-commerce:token                    # 토큰 발급(또는 캐시 조회), 마스킹해서 출력
php artisan naver-commerce:token --show --fresh     # 캐시 삭제 후 재발급, 전체 토큰 출력
php artisan naver-commerce:token:forget             # 캐시된 토큰 삭제

# 임의 엔드포인트 호출. GET 이외 메서드는 --force 가 없으면 확인 프롬프트를 띄움
php artisan naver-commerce:request GET /v1/seller/channels
php artisan naver-commerce:request GET /v1/pay-order/seller/product-orders \
    --query=from=2026-09-01T00:00:00.000+09:00 --query=productOrderStatuses=PAYED --query=productOrderStatuses=DELIVERING
php artisan naver-commerce:request POST /v1/products/search --json='{"page":1,"size":5}' --force
php artisan naver-commerce:request POST /v1/pay-order/seller/product-orders/query --json=@body.json --force

# 변경 상품 주문 조회(폴링용). 24시간을 넘는 범위는 자동으로 나눠서 호출
php artisan naver-commerce:orders:changed --since=6h
php artisan naver-commerce:orders:changed --since=2026-09-01 --until="2026-09-02 12:00" --type=PAYED --all --json

# 카테고리 트리 덤프(리프 약 5,000개) — 로컬 캐시용
php artisan naver-commerce:categories:export storage/app/naver-categories.json --last
```

모든 커맨드는 `--seller=<accountId>` 옵션으로 SELLER 토큰을 사용할 수 있습니다. 오프셋 없는 일시는 KST로 해석합니다. `orders:changed`는 결과가 잘린 경우(`--all` 없이 남은 행이 있거나 요청 상한 도달) 종료 코드 1을 반환하며, `--json` 모드에서는 경고를 stderr로 출력해 stdout이 유효한 JSON으로 유지됩니다.

## 리소스 목록

| 팩토리 | 대상 API |
|---|---|
| `orders()` | 주문 조회·발주/발송·취소/반품/교환 클레임 (`/v1/pay-order/seller`) |
| `products()` | 상품 등록/조회/수정/삭제, 검색, 상태·재고·벌크 변경, 이미지 업로드, 검수 |
| `groupProducts()` | 그룹상품 (`/v2/standard-group-products`) |
| `categories()` | 카테고리, 속성, 표준 옵션, 판매 옵션 가이드, 상품정보제공고시 |
| `catalog()` | 브랜드, 제조사, 카탈로그(모델), 원산지, 사이즈, 태그 |
| `deliveryInfo()` | 묶음배송/희망일배송 그룹, 반품 택배사 |
| `fashionModels()` | 패션모델 |
| `sellerNotices()` | 공지사항 |
| `inquiries()` | 상품 문의(QnA), 고객 문의 및 답변 |
| `settlements()` | 정산·부가세 내역 |
| `logistics()` | N배송 SKU, 물류사, 판매자 창고 |
| `seller()` | 계정, 채널, 주소록, 오늘출발 설정 |
| `commerceSolutions()` | 커머스솔루션 구독/결제 |

각 메서드의 파라미터 키는 [공식 문서](https://apicenter.commerce.naver.com/llms/llms.txt)의 요청 스키마와 동일합니다(래핑이 필요한 경우 메서드가 처리).

## 테스트

```bash
composer test
```

Unit/Feature 테스트는 `Http::fake()`로 동작하며 실제 API를 호출하지 않습니다.

`tests/Integration`은 실제 API를 호출하는 읽기 전용 통합 테스트입니다. 패키지 루트 `.env`에 `NAVER_COMMERCE_CLIENT_ID`/`NAVER_COMMERCE_CLIENT_SECRET`이 있을 때만 실행되고, 없으면 자동으로 skip 됩니다.

```bash
vendor/bin/pest --testsuite=Integration
```

## 기여

[CONTRIBUTING.md](CONTRIBUTING.md)를 참고하세요.

## 라이선스

MIT
