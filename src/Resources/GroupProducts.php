<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 그룹상품 API (/v2/standard-group-products)
 */
class GroupProducts extends Resource
{
    protected const BASE = '/v2/standard-group-products';

    /** POST — 그룹상품 등록 */
    public function create(array $data): array
    {
        return $this->post(self::BASE, $data);
    }

    /** GET /{no} — 그룹상품 조회 */
    public function find(int $groupProductNo): array
    {
        return $this->get(self::BASE."/{$groupProductNo}");
    }

    /** PUT /{no} — 그룹상품 수정 */
    public function update(int $groupProductNo, array $data): array
    {
        return $this->put(self::BASE."/{$groupProductNo}", $data);
    }

    /** DELETE /{no} — 그룹상품 삭제 */
    public function destroy(int $groupProductNo): array
    {
        return $this->delete(self::BASE."/{$groupProductNo}");
    }

    /**
     * GET /status — 그룹상품 요청 결과 조회
     *
     * @param  array  $query  type, requestId
     */
    public function status(array $query = []): array
    {
        return $this->get(self::BASE.'/status', $query);
    }

    /** POST /convert-products — 그룹상품 전환 */
    public function convert(array $data): array
    {
        return $this->post(self::BASE.'/convert-products', $data);
    }

    /** POST /validate-conversion — 그룹상품 전환 유효성 검사 */
    public function validateConversion(array $originProductNos): array
    {
        return $this->post(self::BASE.'/validate-conversion', ['originProductNos' => array_values($originProductNos)]);
    }

    /**
     * POST /release-group — 그룹상품 해제
     *
     * @param  array  $data  targets[](필수), releaseReasonType, releaseDetailReason
     */
    public function release(array $data): array
    {
        return $this->post(self::BASE.'/release-group', $data);
    }

    /** POST /temp-detail-content — 상품 상세 정보 임시 저장 */
    public function saveTempDetailContent(string $content, ?int $detailContentTempId = null): array
    {
        $data = ['content' => $content];

        if ($detailContentTempId !== null) {
            $data['detailContentTempId'] = $detailContentTempId;
        }

        return $this->post(self::BASE.'/temp-detail-content', $data);
    }
}
