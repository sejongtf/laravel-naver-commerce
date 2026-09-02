<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

use InvalidArgumentException;

/**
 * 상품 API (원상품·채널상품·검색·벌크·이미지·검수)
 */
class Products extends Resource
{
    /** POST /v2/products — 상품 등록 */
    public function create(array $data): array
    {
        return $this->post('/v2/products', $data);
    }

    /**
     * POST /v1/products/search — 상품 목록 조회
     *
     * @param  array  $data  searchKeywordType, channelProductNos[], originProductNos[], groupProductNos[], sellerManagementCode, productStatusTypes[], page, size, orderType, periodType, fromDate, toDate
     */
    public function search(array $data = []): array
    {
        return $this->post('/v1/products/search', $data);
    }

    // ----- 원상품 -----

    /** GET /v2/products/origin-products/{no} — 원상품 조회 */
    public function getOrigin(int $originProductNo): array
    {
        return $this->get("/v2/products/origin-products/{$originProductNo}");
    }

    /** PUT /v2/products/origin-products/{no} — 원상품 수정 */
    public function updateOrigin(int $originProductNo, array $data): array
    {
        return $this->put("/v2/products/origin-products/{$originProductNo}", $data);
    }

    /** DELETE /v2/products/origin-products/{no} — 원상품 삭제 */
    public function deleteOrigin(int $originProductNo): array
    {
        return $this->delete("/v2/products/origin-products/{$originProductNo}");
    }

    // ----- 채널상품 -----

    /** GET /v2/products/channel-products/{no} — 채널 상품 조회 */
    public function getChannel(int $channelProductNo): array
    {
        return $this->get("/v2/products/channel-products/{$channelProductNo}");
    }

    /** PUT /v2/products/channel-products/{no} — 채널 상품 수정 */
    public function updateChannel(int $channelProductNo, array $data): array
    {
        return $this->put("/v2/products/channel-products/{$channelProductNo}", $data);
    }

    /** DELETE /v2/products/channel-products/{no} — 채널 상품 삭제 */
    public function deleteChannel(int $channelProductNo): array
    {
        return $this->delete("/v2/products/channel-products/{$channelProductNo}");
    }

    // ----- 상태·재고·가격 -----

    /**
     * PUT /v1/products/origin-products/{no}/change-status — 판매 상태 변경
     *
     * @param  array  $data  statusType(필수), saleStartDate, saleEndDate, stockQuantity
     */
    public function changeStatus(int $originProductNo, array $data): array
    {
        return $this->put("/v1/products/origin-products/{$originProductNo}/change-status", $data);
    }

    /** PUT /v1/products/origin-products/{no}/option-stock — 상품 옵션 재고 변경 */
    public function updateOptionStock(int $originProductNo, array $data): array
    {
        return $this->put("/v1/products/origin-products/{$originProductNo}/option-stock", $data);
    }

    /** PUT /v1/products/origin-products/bulk-update — 상품 벌크 업데이트 */
    public function bulkUpdate(array $data): array
    {
        return $this->put('/v1/products/origin-products/bulk-update', $data);
    }

    /**
     * PATCH /v1/products/origin-products/multi-update — 멀티 상품 변경
     *
     * @param  array<int, array{originProductNo: int, multiUpdateTypes: array, productSalePrice?: array, immediateDiscountPolicy?: array, stockQuantity?: int}>  $items
     */
    public function multiUpdate(array $items): array
    {
        return $this->patch('/v1/products/origin-products/multi-update', ['multiProductUpdateRequestVos' => array_values($items)]);
    }

    /** PUT /v1/products/channel-products/notice/apply — 채널 상품 공지사항 적용 */
    public function applyNotice(array $channelProductNos, ?int $sellerNoticeId = null): array
    {
        $data = ['channelProductNos' => array_values($channelProductNos)];

        if ($sellerNoticeId !== null) {
            $data['sellerNoticeId'] = $sellerNoticeId;
        }

        return $this->put('/v1/products/channel-products/notice/apply', $data);
    }

    // ----- 이미지 -----

    /**
     * POST /v1/product-images/upload — 상품 이미지 다건 등록 (multipart, 최대 10개)
     *
     * @param  array<int, string|array{contents: mixed, filename?: string}>  $files  파일 경로 문자열 또는 ['contents' => resource|string, 'filename' => string]
     * @return array{images: array<int, array{url: string}>}
     */
    public function uploadImages(array $files): array
    {
        if ($files === [] || count($files) > 10) {
            throw new InvalidArgumentException('이미지는 1개 이상 10개 이하로 업로드할 수 있습니다.');
        }

        $multipart = [];

        foreach (array_values($files) as $file) {
            if (is_string($file)) {
                if (! is_file($file)) {
                    throw new InvalidArgumentException("이미지 파일을 찾을 수 없습니다: {$file}");
                }

                $multipart[] = ['name' => 'imageFiles', 'contents' => fopen($file, 'r'), 'filename' => basename($file)];

                continue;
            }

            if (! is_array($file) || ! array_key_exists('contents', $file)) {
                throw new InvalidArgumentException('이미지 항목은 파일 경로 또는 [contents, filename] 배열이어야 합니다.');
            }

            $multipart[] = ['name' => 'imageFiles', 'contents' => $file['contents'], 'filename' => $file['filename'] ?? null];
        }

        $response = $this->client->request('POST', '/v1/product-images/upload', ['multipart' => $multipart]);

        return $this->json($response);
    }

    // ----- 검수(수정 요청) -----

    /** GET /v1/product-inspections/channel-products — 수정 요청 상품 목록 조회 */
    public function inspections(array $query = []): array
    {
        return $this->get('/v1/product-inspections/channel-products', $query);
    }

    /** PUT /v1/product-inspections/channel-product/{no}/restore — 수정 요청 상품 복원 요청 */
    public function restoreInspection(int $channelProductNo): array
    {
        return $this->put("/v1/product-inspections/channel-product/{$channelProductNo}/restore");
    }
}
