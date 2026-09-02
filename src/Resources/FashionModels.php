<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 패션모델 API (/v1/product-fashion-models)
 */
class FashionModels extends Resource
{
    protected const BASE = '/v1/product-fashion-models';

    /** GET — 전체 패션모델 조회 */
    public function all(): array
    {
        return $this->get(self::BASE);
    }

    /** POST — 패션모델 저장 */
    public function create(array $data): array
    {
        return $this->post(self::BASE, $data);
    }

    /** PUT /{id} — 패션모델 수정 */
    public function update(int $fashionModelId, array $data): array
    {
        return $this->put(self::BASE."/{$fashionModelId}", $data);
    }

    /** DELETE /{id} — 패션모델 삭제 */
    public function destroy(int $fashionModelId): array
    {
        return $this->delete(self::BASE."/{$fashionModelId}");
    }
}
