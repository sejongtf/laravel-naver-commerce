<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 브랜드·제조사·카탈로그(모델)·원산지·사이즈·태그 참조 데이터 API
 */
class Catalog extends Resource
{
    /** GET /v1/product-brands — 브랜드 조회 */
    public function brands(string $name): array
    {
        return $this->get('/v1/product-brands', ['name' => $name]);
    }

    /** GET /v1/product-manufacturers — 제조사 조회 */
    public function manufacturers(string $name): array
    {
        return $this->get('/v1/product-manufacturers', ['name' => $name]);
    }

    /**
     * GET /v1/product-models — 카탈로그 조회
     *
     * @param  array  $query  page, size
     */
    public function models(string $name, array $query = []): array
    {
        return $this->get('/v1/product-models', ['name' => $name] + $query);
    }

    /** GET /v1/product-models/{id} — 카탈로그 단건 조회 */
    public function model(int $id): array
    {
        return $this->get("/v1/product-models/{$id}");
    }

    /** GET /v1/product-origin-areas — 원산지 코드 정보 전체 조회 */
    public function originAreas(): array
    {
        return $this->get('/v1/product-origin-areas');
    }

    /**
     * GET /v1/product-origin-areas/query — 원산지 코드 정보 다건 조회
     *
     * @param  array  $query  name, code
     */
    public function searchOriginAreas(array $query = []): array
    {
        return $this->get('/v1/product-origin-areas/query', $query);
    }

    /** GET /v1/product-origin-areas/sub-origin-areas — 하위 원산지 코드 정보 다건 조회 */
    public function subOriginAreas(?string $code = null): array
    {
        return $this->get('/v1/product-origin-areas/sub-origin-areas', ['code' => $code]);
    }

    /** GET /v1/product-sizes — 전체 사이즈 타입 조회 */
    public function sizes(): array
    {
        return $this->get('/v1/product-sizes');
    }

    /** GET /v1/product-sizes/{sizeTypeId} — 사이즈 타입 조회 */
    public function size(int $sizeTypeId): array
    {
        return $this->get("/v1/product-sizes/{$sizeTypeId}");
    }

    /** GET /v2/tags/recommend-tags — 추천 태그 검색 목록 조회 */
    public function recommendTags(string $keyword): array
    {
        return $this->get('/v2/tags/recommend-tags', ['keyword' => $keyword]);
    }

    /** GET /v2/tags/restricted-tags — 제한 태그 여부 조회 */
    public function restrictedTags(array $tags): array
    {
        return $this->get('/v2/tags/restricted-tags', ['tags' => array_values($tags)]);
    }
}
