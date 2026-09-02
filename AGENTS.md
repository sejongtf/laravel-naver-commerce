# AGENTS.md

Instructions for AI coding agents working in this repository. Human-oriented guidelines live in [CONTRIBUTING.md](CONTRIBUTING.md); read that too.

## What this is

`sejongtf/laravel-naver-commerce` is a Laravel 13 package wrapping the Naver Commerce API (`https://api.commerce.naver.com/external`). PHP 8.4+, Pest 5, Orchestra Testbench 11. Entry point is `Sejongtf\LaravelNaverCommerce\NaverCommerce` (facade `NaverCommerce`), which hands out resource classes (`orders()`, `products()`, …) that return decoded JSON arrays.

## Commands

```bash
composer install
vendor/bin/pest                          # unit + feature (fully faked, no network)
vendor/bin/pest --testsuite=Integration  # live read-only API calls; skipped without .env credentials
vendor/bin/pest --filter="<test name>"
composer lint                            # Pint (laravel preset), check only; `composer format` fixes
composer analyse                         # Larastan level 6
composer check                           # lint + analyse + test (same as CI)
```

Run `composer check` before reporting any code change as done. If you touched `Client`, `TokenManager`, or `QueryString` and `.env` credentials exist, also run the Integration suite.

## Source of truth for the API

- Index: `https://apicenter.commerce.naver.com/llms/llms.txt` (fetch with `curl`; `WebFetch` is blocked for this host).
- Per-endpoint spec: `https://apicenter.commerce.naver.com/llms/<method>-<path-with-dashes>.md`, e.g. `post-v1-pay-order-seller-product-orders-dispatch.md`. Each page lists parameters with their location (path / query / body) and whether they are required.
- Conventions: `intro-인증.md` (auth, signature), `intro-restful-api.md` (dates, status codes), `intro-제약사항.md` (rate limits), `intro-문제해결.md` (gateway error codes).

Do not invent parameter names. If a spec page is unavailable, say so rather than guessing.

## Hard rules

1. **Resource method shape**: path params positional → `array $data` (JSON body) → `array $query = []`. Return `array`. No DTOs.
2. **Never name a resource method `get`/`post`/`put`/`patch`/`delete`** — those are protected helpers in `Resource`. Use `find`, `destroy`, `list`, or a domain verb.
3. **All HTTP goes through `Http\Client`.** Do not use the `Http` facade in resources. Query strings must come from `QueryString::build()` (repeated keys for arrays; never `http_build_query`).
4. **Never log, print, or transmit `client_secret`.** It is only a bcrypt salt for `Signature::generate()`.
5. **Integration tests are read-only.** Never add or run anything that mutates seller data (create/update/delete products, dispatch orders, answer inquiries, …). The `.env` credentials point at a live production store.
6. **Every new resource method gets a dataset row** in `tests/Feature/Resources/ResourcesTest.php` asserting method, full URL with query, and JSON body.
7. **Facade docblock**: when adding a factory method to `NaverCommerce`, add the matching `@method static` line to `Facades/NaverCommerce`.
8. **Docs in English by default.** `README.md` (EN) and `README.ko.md` (KO) must be updated together. Code comments in new code are English.
9. **Commits**: Conventional Commits (`feat:`, `fix:`, `docs:`, …). Do not add `Co-Authored-By` trailers. Do not commit unless asked.
10. Do not add runtime dependencies or change the token cache key format without explicit approval.
11. **Pint and PHPStan must pass.** Fix PHPStan findings at the source; do not add `@phpstan-ignore` comments or baseline entries. The only ignored identifier is `missingType.iterableValue` (plain `array` return types are intentional).

## Where things are

| Concern | File |
|---|---|
| Signature (`client_secret_sign`) | `src/Auth/Signature.php` |
| Token issue / cache / forget | `src/Auth/TokenManager.php` |
| Auth header, `GW.AUTHN` retry, 5xx retry, exception mapping, multipart | `src/Http/Client.php` |
| Query string + body normalization (dates, bools, enums) | `src/Http/QueryString.php` |
| KST date helpers | `src/Support/DateFormatter.php` |
| Exception hierarchy | `src/Exceptions/` |
| Endpoint methods | `src/Resources/*.php` |
| Artisan commands (`naver-commerce:token`, `:token:forget`, `:ping`, `:request`, `:orders:changed`, `:categories:export`) | `src/Console/` |
| Config defaults | `config/naver-commerce.php` |
| Pint / PHPStan config | `pint.json`, `phpstan.neon.dist` |
| Test bootstrap, `fakeApi()`, `assertApiSent()` | `tests/TestCase.php` |
| Command tests (assert on `Artisan::output()`) | `tests/Feature/Console/` |
| Live tests (skip without `.env`) | `tests/Integration/` |

## Known gotchas

- Laravel's `PendingRequest::send()` drops files attached via `attach()` unless `['multipart' => []]` is passed as options. `Client::send()` already does this; keep it.
- bcrypt normalizes the last salt character (`…uv` → `…uu`), so compare only the first 21 chars of the salt in signature tests.
- The token endpoint takes `application/x-www-form-urlencoded`, not JSON. Form-decoded values are strings in `Http::fake()` assertions (`timestamp` is numeric string).
- Testbench does not load the package-root `.env`; `tests/Integration/IntegrationTestCase.php` reads it explicitly with Dotenv.
- `expectsOutputToContain()` matches at most one substring per written line (Mockery picks the first matching expectation). Command tests assert on `Artisan::output()` instead.
- `GET /v1/pay-order/seller/product-orders/last-changed-statuses` rejects windows longer than 24 h with `400 104140` (verified live). `orders:changed` splits longer ranges into 24 h chunks.
- API date-times are KST (`+09:00`) with milliseconds: `Y-m-d\TH:i:s.vP`. Date-only params (`yyyy-MM-dd`) are passed as strings by callers.
