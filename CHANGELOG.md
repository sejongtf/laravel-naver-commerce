# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres to
[Semantic Versioning](https://semver.org/). Tags are plain version numbers (`0.1.0`), without a `v` prefix.

## [Unreleased]

## [0.1.0] - 2026-09-02

### Added

- Naver Commerce API client for Laravel 13 / PHP 8.4: `NaverCommerce` entry point and facade,
  resource classes covering all 116 documented endpoints (orders, products, group products,
  categories, catalog, delivery info, fashion models, seller notices, inquiries, settlements,
  logistics, seller, commerce solutions), returning decoded JSON arrays.
- OAuth2 client-credentials authentication with bcrypt `client_secret_sign`, token caching per
  type/account, `SELF` default and `forSeller($accountId)` for `SELLER` tokens.
- HTTP layer: automatic re-authentication on `401 GW.AUTHN`, configurable 5xx/connection retry,
  Spring-style repeated-key query strings, KST date-time normalization, multipart image upload.
- Exception hierarchy (`NaverCommerceException`, `AuthenticationException`, `RateLimitException`,
  `ApiException`) exposing status, error code, trace ID, and rate-limit/quota headers.
- Artisan commands: `naver-commerce:ping`, `naver-commerce:token`, `naver-commerce:token:forget`,
  `naver-commerce:request`, `naver-commerce:orders:changed` (24 h window splitting, `more` pagination),
  `naver-commerce:categories:export`.
- Pest test suite (unit, feature with `Http::fake()`, read-only live integration), GitHub Actions CI
  with Pint and PHPStan (Larastan level 6).
- English and Korean READMEs, `CONTRIBUTING.md`, `AGENTS.md`.

[Unreleased]: https://github.com/sejongtf/laravel-naver-commerce/compare/0.1.0...0.x
[0.1.0]: https://github.com/sejongtf/laravel-naver-commerce/releases/tag/0.1.0
