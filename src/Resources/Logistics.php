<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 물류(N배송 SKU·물류사·창고) API
 */
class Logistics extends Resource
{
    /** GET /v1/logistics/logistics-companies — 물류사 연동 정보 조회 */
    public function companies(): array
    {
        return $this->get('/v1/logistics/logistics-companies');
    }

    /** GET /v1/logistics/outbound-locations — 판매자 창고 정보 조회 */
    public function outboundLocations(): array
    {
        return $this->get('/v1/logistics/outbound-locations');
    }

    /** GET /v2/logistics/products/sellers/me/skus/{nsId} — SKU 조회 V2 */
    public function sku(string $nsId): array
    {
        return $this->get("/v2/logistics/products/sellers/me/skus/{$nsId}");
    }

    /**
     * GET /v1/logistics/products/sellers/me/skus/{nsId} — 네이버 SKU 조회
     *
     * @deprecated v2 sku() 사용 권장
     */
    public function skuLegacy(string $nsId): array
    {
        return $this->get("/v1/logistics/products/sellers/me/skus/{$nsId}");
    }

    /**
     * GET /v1/logistics/products/sellers/me/skus/{nsId}/product-mappings — 네이버 SKU 연결상품 조회
     *
     * @param  array  $query  page, size
     */
    public function skuProductMappings(string $nsId, array $query = []): array
    {
        return $this->get("/v1/logistics/products/sellers/me/skus/{$nsId}/product-mappings", $query);
    }

    /**
     * POST /v1/logistics/products/sellers/me/skus/query-paged-list — 네이버 SKU 목록 조회
     *
     * @param  array  $data  searchKeywordType, nsIds[], nsBarcodes[], periodType, fromDate, toDate, page, size, orderType
     */
    public function searchSkus(array $data = []): array
    {
        return $this->post('/v1/logistics/products/sellers/me/skus/query-paged-list', $data);
    }
}
