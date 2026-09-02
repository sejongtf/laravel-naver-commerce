<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 묶음배송 그룹·희망일배송 그룹·반품 택배사 API
 */
class DeliveryInfo extends Resource
{
    protected const BASE = '/v1/product-delivery-info';

    // ----- 묶음배송 그룹 -----

    /**
     * GET /bundle-groups — 묶음배송 그룹 다건 조회
     *
     * @param  array  $query  name, baseGroup, usable, page, size
     */
    public function bundleGroups(array $query = []): array
    {
        return $this->get(self::BASE.'/bundle-groups', $query);
    }

    /** GET /bundle-groups/{id} — 묶음배송 그룹 단건 조회 */
    public function bundleGroup(int $deliveryBundleGroupId): array
    {
        return $this->get(self::BASE."/bundle-groups/{$deliveryBundleGroupId}");
    }

    /** POST /bundle-groups — 묶음배송 그룹 등록 */
    public function createBundleGroup(array $deliveryBundleGroup): array
    {
        return $this->post(self::BASE.'/bundle-groups', $this->wrap('deliveryBundleGroup', $deliveryBundleGroup));
    }

    /** PUT /bundle-groups/{id} — 묶음배송 그룹 수정 */
    public function updateBundleGroup(int $deliveryBundleGroupId, array $deliveryBundleGroup): array
    {
        return $this->put(self::BASE."/bundle-groups/{$deliveryBundleGroupId}", $this->wrap('deliveryBundleGroup', $deliveryBundleGroup));
    }

    // ----- 희망일배송 그룹 -----

    /**
     * GET /hope-delivery-groups — 희망일배송 그룹 다건 조회
     *
     * @param  array  $query  name, baseGroup, usable, page, size
     */
    public function hopeDeliveryGroups(array $query = []): array
    {
        return $this->get(self::BASE.'/hope-delivery-groups', $query);
    }

    /** GET /hope-delivery-groups/{id} — 희망일배송 그룹 단건 조회 */
    public function hopeDeliveryGroup(int $hopeDeliveryGroupId): array
    {
        return $this->get(self::BASE."/hope-delivery-groups/{$hopeDeliveryGroupId}");
    }

    /** POST /hope-delivery-groups — 희망일배송 그룹 등록 */
    public function createHopeDeliveryGroup(array $hopeDeliveryGroup): array
    {
        return $this->post(self::BASE.'/hope-delivery-groups', $this->wrap('hopeDeliveryGroup', $hopeDeliveryGroup));
    }

    /** PUT /hope-delivery-groups/{id} — 희망일배송 그룹 수정 */
    public function updateHopeDeliveryGroup(int $hopeDeliveryGroupId, array $hopeDeliveryGroup): array
    {
        return $this->put(self::BASE."/hope-delivery-groups/{$hopeDeliveryGroupId}", $this->wrap('hopeDeliveryGroup', $hopeDeliveryGroup));
    }

    // ----- 반품 택배사 -----

    /** GET /v2/product-delivery-info/return-delivery-companies — 반품 택배사 다건 조회 */
    public function returnDeliveryCompanies(?string $name = null): array
    {
        return $this->get('/v2/product-delivery-info/return-delivery-companies', ['name' => $name]);
    }

    /**
     * 이미 래핑된 배열({key: {...}})이면 그대로, 아니면 래핑한다.
     */
    protected function wrap(string $key, array $data): array
    {
        return array_key_exists($key, $data) && count($data) === 1 ? $data : [$key => $data];
    }
}
