<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 카테고리·속성·옵션·상품정보제공고시 API
 */
class Categories extends Resource
{
    /**
     * GET /v1/categories — 전체 카테고리 조회
     *
     * @param  bool|null  $last  true 이면 리프 카테고리만
     */
    public function all(?bool $last = null): array
    {
        return $this->get('/v1/categories', ['last' => $last]);
    }

    /** GET /v1/categories/{id} — 카테고리 조회 */
    public function find(string $categoryId): array
    {
        return $this->get("/v1/categories/{$categoryId}");
    }

    /** GET /v1/categories/{id}/sub-categories — 하위 카테고리 조회 */
    public function subCategories(string $categoryId): array
    {
        return $this->get("/v1/categories/{$categoryId}/sub-categories");
    }

    /** GET /v1/product-attributes/attributes — 카테고리별 속성 조회 */
    public function attributes(string $categoryId): array
    {
        return $this->get('/v1/product-attributes/attributes', ['categoryId' => $categoryId]);
    }

    /** GET /v1/product-attributes/attribute-values — 카테고리별 속성값 조회 */
    public function attributeValues(string $categoryId): array
    {
        return $this->get('/v1/product-attributes/attribute-values', ['categoryId' => $categoryId]);
    }

    /** GET /v1/product-attributes/attribute-value-units — 전체 속성값 단위 조회 */
    public function attributeValueUnits(): array
    {
        return $this->get('/v1/product-attributes/attribute-value-units');
    }

    /** GET /v1/options/standard-options — 카테고리별 표준형 옵션 조회 */
    public function standardOptions(string $categoryId): array
    {
        return $this->get('/v1/options/standard-options', ['categoryId' => $categoryId]);
    }

    /** GET /v2/standard-purchase-option-guides — 판매 옵션 정보 조회 */
    public function purchaseOptionGuides(string $categoryId): array
    {
        return $this->get('/v2/standard-purchase-option-guides', ['categoryId' => $categoryId]);
    }

    /** GET /v1/products-for-provided-notice — 상품정보제공고시 상품군 목록 조회 */
    public function providedNoticeTypes(?string $categoryId = null): array
    {
        return $this->get('/v1/products-for-provided-notice', ['categoryId' => $categoryId]);
    }

    /** GET /v1/products-for-provided-notice/{type} — 상품정보제공고시 상품군 단건 조회 */
    public function providedNoticeType(string $productInfoProvidedNoticeType): array
    {
        return $this->get("/v1/products-for-provided-notice/{$productInfoProvidedNoticeType}");
    }
}
