# laravel-naver-commerce

[![Tests](https://github.com/sejongtf/laravel-naver-commerce/actions/workflows/tests.yml/badge.svg?branch=0.x)](https://github.com/sejongtf/laravel-naver-commerce/actions/workflows/tests.yml)

[한국어 문서](README.ko.md)

A Laravel client for the [Naver Commerce API](https://apicenter.commerce.naver.com/docs/introduction) (SmartStore).
It wraps all 116 endpoints across every domain (auth, orders, products, settlements, inquiries, logistics, seller info, commerce solutions) as domain-specific resource classes.

- OAuth 2.0 Client Credentials: automatic token issuance, caching, and re-issuance (one automatic retry on `401 GW.AUTHN`)
- bcrypt-based `client_secret_sign` generation
- Spring-style query strings (arrays as repeated keys) and automatic KST ISO 8601 date formatting
- Gateway/API errors mapped to typed exceptions with `traceId` and rate-limit header accessors
- `SELF` and `SELLER` token types (`forSeller()`)

## Requirements

- PHP 8.4+
- Laravel 13

## Installation

```bash
composer require sejongtf/laravel-naver-commerce
php artisan vendor:publish --tag=naver-commerce-config   # optional
```

`.env`:

```dotenv
NAVER_COMMERCE_CLIENT_ID=your-application-id
NAVER_COMMERCE_CLIENT_SECRET='$2a$10$...'   # application secret (bcrypt salt). Quote it because of the `$` characters.
```

Optional settings:

| Key | Default | Description |
|---|---|---|
| `NAVER_COMMERCE_TOKEN_TYPE` | `SELF` | Default token type (`SELF` / `SELLER`) |
| `NAVER_COMMERCE_ACCOUNT_ID` | – | Seller ID when the default type is `SELLER` |
| `NAVER_COMMERCE_CACHE_STORE` | default store | Cache store for tokens |
| `NAVER_COMMERCE_TIMEOUT` | `30` | Response timeout in seconds |
| `NAVER_COMMERCE_RETRY_TIMES` | `0` | Retries for 5xx / connection errors |

## Usage

```php
use Sejongtf\LaravelNaverCommerce\Facades\NaverCommerce;

// Seller account
$account = NaverCommerce::seller()->account();

// Poll orders changed in the last hour
$changed = NaverCommerce::orders()->lastChangedStatuses(now()->subHour());
$ids = collect($changed['data']['lastChangeStatuses'] ?? [])->pluck('productOrderId');

// Product order details (bulk)
$orders = NaverCommerce::orders()->query($ids->all());

// Confirm, then dispatch
NaverCommerce::orders()->confirm(['2024010112345']);
NaverCommerce::orders()->dispatch([
    ['productOrderId' => '2024010112345', 'deliveryMethod' => 'DELIVERY', 'deliveryCompanyCode' => 'CJGLS', 'trackingNumber' => '1234567890'],
]);

// Conditional search — arrays are sent as repeated keys (productOrderStatuses=PAYED&productOrderStatuses=DELIVERING)
$paid = NaverCommerce::orders()->productOrders(now()->subDay(), [
    'productOrderStatuses' => ['PAYED', 'DELIVERING'],
    'pageSize' => 300,
]);

// Upload images, then create a product
$images = NaverCommerce::products()->uploadImages([storage_path('app/main.jpg')]);
NaverCommerce::products()->create([
    'originProduct' => [
        'statusType' => 'SALE',
        'name' => 'Product name',
        'salePrice' => 10000,
        'images' => ['representativeImage' => ['url' => $images['images'][0]['url']]],
        // ...
    ],
    'smartstoreChannelProduct' => ['naverShoppingRegistration' => true, 'channelProductDisplayStatusType' => 'ON'],
]);
```

Every method returns the decoded JSON response as an `array`. Any `DateTimeInterface` (including Carbon) in a query or body is formatted as `2024-01-01T00:00:00.000+09:00`. For parameters that expect `yyyy-MM-dd` (settlements, etc.) pass a string or use `DateFormatter::date()`.

### Calling on behalf of a seller (SELLER token)

```php
NaverCommerce::forSeller('seller-account-id')->orders()->productOrders(now()->subDay());
```

Tokens are cached per `{type}:{accountId}`.

### Exceptions

| Exception | Condition |
|---|---|
| `AuthenticationException` | 401, token issuance failure, invalid credentials. `isTokenExpired()` |
| `RateLimitException` | 429 (`GW.RATE_LIMIT`, `GW.QUOTA_LIMIT`). `replenishRate()`, `remaining()`, `quotaRemaining()`, … |
| `ApiException` | Any other 4xx/5xx, connection errors |

All extend `NaverCommerceException`, which exposes `status()`, `errorCode()`, `traceId()`, `errors()` (response body), and `response()`.

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

Headers of the last response (rate limits, trace ID) are available via `NaverCommerce::lastResponse()?->header('GNCP-GW-RateLimit-Remaining')`.

### Unmapped or new endpoints

```php
NaverCommerce::get('/v1/some/new-endpoint', ['page' => 1]);
NaverCommerce::post('/v1/some/new-endpoint', ['body' => 'x'], ['query' => 'y']);
NaverCommerce::request('PUT', '/v1/...', ['json' => [...], 'query' => [...]]); // returns Illuminate Response
```

### Artisan commands

```bash
php artisan naver-commerce:ping                     # fetch seller account + channels to verify credentials
php artisan naver-commerce:token                    # issue (or read cached) token, printed masked
php artisan naver-commerce:token --show --fresh     # discard cache, reissue, print the full token
php artisan naver-commerce:token:forget             # drop the cached token

# Call any endpoint; non-GET methods ask for confirmation unless --force is given
php artisan naver-commerce:request GET /v1/seller/channels
php artisan naver-commerce:request GET /v1/pay-order/seller/product-orders \
    --query=from=2026-09-01T00:00:00.000+09:00 --query=productOrderStatuses=PAYED --query=productOrderStatuses=DELIVERING
php artisan naver-commerce:request POST /v1/products/search --json='{"page":1,"size":5}' --force
php artisan naver-commerce:request POST /v1/pay-order/seller/product-orders/query --json=@body.json --force

# Changed product orders (polling primitive); windows over 24 h are split automatically
php artisan naver-commerce:orders:changed --since=6h
php artisan naver-commerce:orders:changed --since=2026-09-01 --until="2026-09-02 12:00" --type=PAYED --all --json

# Dump the category tree (about 5,000 leaf categories) for local caching
php artisan naver-commerce:categories:export storage/app/naver-categories.json --last
```

Every command accepts `--seller=<accountId>` to operate with a SELLER token. Date-times without an offset are interpreted as KST. `orders:changed` exits with code 1 when the result is truncated (more rows left without `--all`, or the request cap reached); in `--json` mode the warning goes to stderr so stdout stays valid JSON.

## Resources

| Factory | API area |
|---|---|
| `orders()` | Order lookup, confirm/dispatch, cancel/return/exchange claims (`/v1/pay-order/seller`) |
| `products()` | Product CRUD, search, status/stock/bulk updates, image upload, inspections |
| `groupProducts()` | Group products (`/v2/standard-group-products`) |
| `categories()` | Categories, attributes, standard options, purchase option guides, provided-notice types |
| `catalog()` | Brands, manufacturers, catalog models, origin areas, sizes, tags |
| `deliveryInfo()` | Bundle / hope-delivery groups, return delivery companies |
| `fashionModels()` | Fashion models |
| `sellerNotices()` | Seller notices |
| `inquiries()` | Product Q&A, customer inquiries and answers |
| `settlements()` | Settlement and VAT reports |
| `logistics()` | N-delivery SKUs, logistics companies, outbound locations |
| `seller()` | Account, channels, address books, same-day dispatch settings |
| `commerceSolutions()` | Commerce solution subscriptions and payments |

Parameter keys match the request schemas in the [official docs](https://apicenter.commerce.naver.com/llms/llms.txt); methods add wrapper keys where the API requires them.

## Testing

```bash
composer test
```

Unit/Feature tests run against `Http::fake()` and never hit the real API.

`tests/Integration` contains read-only tests against the live API. They run only when `NAVER_COMMERCE_CLIENT_ID` / `NAVER_COMMERCE_CLIENT_SECRET` are present in the package-root `.env`, and are skipped otherwise.

```bash
vendor/bin/pest --testsuite=Integration
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT
