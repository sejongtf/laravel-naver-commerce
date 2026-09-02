<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 공지사항 API (/v1/contents/seller-notices)
 */
class SellerNotices extends Resource
{
    protected const BASE = '/v1/contents/seller-notices';

    /**
     * GET — 공지사항 목록 조회
     *
     * @param  array  $query  page, size
     */
    public function list(array $query = []): array
    {
        return $this->get(self::BASE, $query);
    }

    /** GET /{id} — 공지사항 단건 조회 */
    public function find(int $sellerNoticeId): array
    {
        return $this->get(self::BASE."/{$sellerNoticeId}");
    }

    /** POST — 공지사항 등록 */
    public function create(array $data): array
    {
        return $this->post(self::BASE, $data);
    }

    /** PUT /{id} — 공지사항 수정 */
    public function update(int $sellerNoticeId, array $data): array
    {
        return $this->put(self::BASE."/{$sellerNoticeId}", $data);
    }

    /** DELETE /{id} — 공지사항 삭제 */
    public function destroy(int $sellerNoticeId): array
    {
        return $this->delete(self::BASE."/{$sellerNoticeId}");
    }
}
