<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

use DateTimeInterface;

/**
 * 주문 API (/v1/pay-order/seller)
 */
class Orders extends Resource
{
    protected const BASE = '/v1/pay-order/seller';

    /** GET /orders/{orderId}/product-order-ids — 상품 주문 목록 조회 */
    public function productOrderIds(string $orderId): array
    {
        return $this->get(self::BASE."/orders/{$orderId}/product-order-ids");
    }

    /**
     * GET /product-orders — 조건형 상품 주문 상세 내역 조회
     *
     * @param  array  $query  to, rangeType, productOrderStatuses[], claimStatuses[], placeOrderStatusType, fulfillment, pageSize, page, quantityClaimCompatibility
     */
    public function productOrders(DateTimeInterface|string $from, array $query = []): array
    {
        return $this->get(self::BASE.'/product-orders', ['from' => $from] + $query);
    }

    /**
     * GET /product-orders/last-changed-statuses — 변경 상품 주문 내역 조회
     *
     * @param  array  $query  lastChangedTo, lastChangedType, moreSequence, limitCount
     */
    public function lastChangedStatuses(DateTimeInterface|string $lastChangedFrom, array $query = []): array
    {
        return $this->get(self::BASE.'/product-orders/last-changed-statuses', ['lastChangedFrom' => $lastChangedFrom] + $query);
    }

    /** POST /product-orders/query — 상품 주문 상세 내역 조회(다건) */
    public function query(array $productOrderIds, ?bool $quantityClaimCompatibility = null): array
    {
        $data = ['productOrderIds' => array_values($productOrderIds)];

        if ($quantityClaimCompatibility !== null) {
            $data['quantityClaimCompatibility'] = $quantityClaimCompatibility;
        }

        return $this->post(self::BASE.'/product-orders/query', $data);
    }

    /** POST /product-orders/confirm — 발주 확인 처리 */
    public function confirm(array $productOrderIds): array
    {
        return $this->post(self::BASE.'/product-orders/confirm', ['productOrderIds' => array_values($productOrderIds)]);
    }

    /**
     * POST /product-orders/dispatch — 발송 처리
     *
     * @param  array<int, array{productOrderId: string, deliveryMethod: string, deliveryCompanyCode?: string, trackingNumber?: string, dispatchDate?: mixed}>  $dispatchProductOrders
     */
    public function dispatch(array $dispatchProductOrders): array
    {
        return $this->post(self::BASE.'/product-orders/dispatch', ['dispatchProductOrders' => array_values($dispatchProductOrders)]);
    }

    /** POST /product-orders/{id}/delay — 발송 지연 처리 */
    public function delay(string $productOrderId, array $data): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/delay", $data);
    }

    /** POST /product-orders/{id}/hope-delivery/change — 배송 희망일 변경 처리 */
    public function changeHopeDelivery(string $productOrderId, array $data): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/hope-delivery/change", $data);
    }

    // ----- 취소 -----

    /** POST .../claim/cancel/request — 취소 요청 */
    public function requestCancel(string $productOrderId, array $data): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/cancel/request", $data);
    }

    /** POST .../claim/cancel/approve — 취소 요청 승인 */
    public function approveCancel(string $productOrderId): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/cancel/approve");
    }

    // ----- 반품 -----

    /** POST .../claim/return/request — 반품 요청 */
    public function requestReturn(string $productOrderId, array $data): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/return/request", $data);
    }

    /** POST .../claim/return/approve — 반품 승인 */
    public function approveReturn(string $productOrderId): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/return/approve");
    }

    /** POST .../claim/return/reject — 반품 거부(철회) */
    public function rejectReturn(string $productOrderId, string $rejectReturnReason): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/return/reject", ['rejectReturnReason' => $rejectReturnReason]);
    }

    /** POST .../claim/return/holdback — 반품 보류 */
    public function holdbackReturn(string $productOrderId, array $data): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/return/holdback", $data);
    }

    /** POST .../claim/return/holdback/release — 반품 보류 해제 */
    public function releaseReturnHoldback(string $productOrderId): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/return/holdback/release");
    }

    // ----- 교환 -----

    /** POST .../claim/exchange/collect/approve — 교환 수거 완료 */
    public function approveExchangeCollect(string $productOrderId): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/exchange/collect/approve");
    }

    /** POST .../claim/exchange/dispatch — 교환 재배송 처리 */
    public function dispatchExchange(string $productOrderId, array $data = []): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/exchange/dispatch", $data);
    }

    /** POST .../claim/exchange/reject — 교환 거부(철회) */
    public function rejectExchange(string $productOrderId, string $rejectExchangeReason): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/exchange/reject", ['rejectExchangeReason' => $rejectExchangeReason]);
    }

    /** POST .../claim/exchange/holdback — 교환 보류 */
    public function holdbackExchange(string $productOrderId, array $data): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/exchange/holdback", $data);
    }

    /** POST .../claim/exchange/holdback/release — 교환 보류 해제 */
    public function releaseExchangeHoldback(string $productOrderId): array
    {
        return $this->post(self::BASE."/product-orders/{$productOrderId}/claim/exchange/holdback/release");
    }
}
