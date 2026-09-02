# Contributing

Thanks for helping improve `laravel-naver-commerce`. This document covers the development workflow and the conventions the codebase follows.

## Setup

```bash
git clone https://github.com/sejongtf/laravel-naver-commerce.git
cd laravel-naver-commerce
composer install
composer test
```

Requirements: PHP 8.4+ (required by Pest 5 / PHPUnit 13), Composer 2. The package targets Laravel 13 (`illuminate/*` ^13) and is tested with Pest 5 on Orchestra Testbench 11.

## Project layout

```
config/naver-commerce.php   Published config (credentials, base URL, token cache, retry)
src/
  NaverCommerce.php          Entry point: resource factories, forSeller(), generic get/post/…
  NaverCommerceServiceProvider.php
  Facades/NaverCommerce.php
  Auth/Signature.php         bcrypt client_secret_sign
  Auth/TokenManager.php      Token issuance + cache (per type/accountId)
  Http/Client.php            Auth header, GW.AUTHN retry, 5xx retry, error → exception
  Http/QueryString.php       Spring-style query builder, body normalization
  Console/                   Artisan commands (token, token:forget, ping, request, orders:changed, categories:export)
  Support/DateFormatter.php  KST ISO 8601 helpers
  Exceptions/                NaverCommerceException, AuthenticationException, RateLimitException, ApiException
  Resources/                 One class per API domain, extends Resource
tests/
  Unit/                      Pure PHP (signature, query string, dates)
  Feature/                   Testbench + Http::fake (token, client, resource → request mapping)
  Integration/               Live read-only API calls; skipped without .env credentials
```

## Design rules

These are the conventions to keep when adding or changing code.

### Resources

- One resource class per API domain under `src/Resources/`, extending `Resource`. Register a factory method on `NaverCommerce` and add the `@method` docblock to `Facades/NaverCommerce`.
- Method signature order: path parameters as positional arguments, then `array $data` for a JSON body, then `array $query = []` for query parameters. Read endpoints with a clearly required query parameter take it as a named argument and merge the rest from `array $query = []`.
- Every method returns `array` (decoded JSON; `[]` for 204). Do not introduce DTOs or response objects.
- Use the request schema's own key names. Only add wrapper keys the API requires (e.g. `deliveryBundleGroup`, `multiProductUpdateRequestVos`) and accept an already-wrapped array where practical.
- Do not name resource methods `get`, `post`, `put`, `patch`, or `delete`; those are protected helpers on `Resource`. Use `find` / `destroy` / `list` instead.
- Add a one-line docblock with the HTTP method and path (`/** GET /v1/... — description */`) on every resource method.

### HTTP layer

- All requests go through `Http\Client::request()`. Do not call the `Http` facade from resources.
- Query strings must be built by `QueryString::build()` (repeated keys for arrays, `true`/`false` for booleans, KST ISO 8601 for dates, nulls dropped). Never use `http_build_query` or Laravel's `$query` argument for this API.
- Multipart uploads pass `['multipart' => [...]]` to `Client::request()`; the client must call `send()` with `['multipart' => []]` so attached files are merged.
- Non-2xx responses become exceptions via `NaverCommerceException::fromResponse()`. Preserve `code`, `message`, `traceId`, and the original `Response`.

### Authentication

- The client secret is only used as the bcrypt salt for `client_secret_sign`. It must never be sent over the network or logged.
- Token cache keys are `{prefix}:{sha1(client_id)[:12]}:{TYPE}:{accountId|self}`. Changing this format invalidates cached tokens for users, so treat it as a breaking change.

### Style

- PHP 8.4 features are fine (readonly, enums, `match`, first-class callables). Declare `strict` parameter and return types.
- Keep comments and docblocks in English in new code. Existing Korean API terms (e.g. 발주 확인) may be kept in docblocks where they map to the official Korean endpoint names.
- No new runtime dependencies without discussion.
- Code style is enforced by [Laravel Pint](https://laravel.com/docs/pint) (`laravel` preset): run `composer format` before committing, `composer lint` to check.
- Static analysis runs with [Larastan](https://github.com/larastan/larastan) at level 6 (`composer analyse`). Plain `array` types without value types are allowed on purpose (resources return decoded JSON); other findings should be fixed at the source rather than ignored.

## Tests

- Run everything: `composer test` (or `vendor/bin/pest`). `composer check` runs Pint, PHPStan, and the tests together, which is what CI does.
- Feature tests use `Http::preventStrayRequests()`; any unfaked request fails the test.
- Every new resource method needs a row in the dataset in `tests/Feature/Resources/ResourcesTest.php` (`[closure, method, path+query, expected JSON body|null]`).
- Behavior changes in `Client`, `TokenManager`, or `QueryString` need a dedicated test in `tests/Feature` or `tests/Unit`.
- Integration tests (`tests/Integration`) hit the live API and run only when the package-root `.env` provides `NAVER_COMMERCE_CLIENT_ID` and `NAVER_COMMERCE_CLIENT_SECRET`. They must stay read-only: never add tests that create, update, delete, dispatch, or otherwise mutate seller data. The credentials belong to a production store.

## Adding a new endpoint

1. Check the endpoint's spec in the official docs. The LLM-friendly index is `https://apicenter.commerce.naver.com/llms/llms.txt`; each endpoint has a `.md` page linked from there with parameter locations (path/query/body) and required flags.
2. Add the method to the matching resource class following the signature rules above. Create a new resource only for a genuinely new domain.
3. Add a dataset row to `ResourcesTest.php` covering method, URL (including query string), and body.
4. Update the resource table in `README.md` and `README.ko.md` if a new resource or notable capability was added.

## Commits and pull requests

- Use [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`.
- Do not add `Co-Authored-By` trailers.
- Keep PRs focused. Include the test output or a note on what was verified, and mention if integration tests were run.

## Documentation

- Documentation is written in English by default. `README.md` is the canonical English README; `README.ko.md` is the Korean translation and must be kept in sync when the English version changes.
- `AGENTS.md` holds instructions for AI coding agents; `CLAUDE.md` imports it. Update `AGENTS.md` when conventions in this file change.

## Security

Never commit `.env`, `auth.json`, or any real credentials. If you find a security issue, contact the maintainers privately instead of opening a public issue.
