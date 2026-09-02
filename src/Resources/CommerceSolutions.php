<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 커머스솔루션 API (/v1/commerce-solutions)
 */
class CommerceSolutions extends Resource
{
    protected const BASE = '/v1/commerce-solutions';

    /** GET /seller-info-by-token — 판매자 인증 JWE 해석 */
    public function sellerInfoByToken(string $token): array
    {
        return $this->get(self::BASE.'/seller-info-by-token', ['token' => $token]);
    }

    /** GET /subscriptions/{accountUid} — 사용 상태 조회 */
    public function subscription(string $accountUid): array
    {
        return $this->get(self::BASE."/subscriptions/{$accountUid}");
    }

    /**
     * GET /transactions — 비즈월렛 결제 내역 조회
     *
     * @param  array  $query  paymentConfirmStartDate(필수), paymentConfirmEndDate(필수), transactionId, originalTransactionId, solutionName, transactionType, planId, accountUid
     */
    public function transactions(array $query): array
    {
        return $this->get(self::BASE.'/transactions', $query);
    }

    /** POST /external-transactions — 외부 개발사 자체 결제 내역 전송 */
    public function sendExternalTransaction(array $data): array
    {
        return $this->post(self::BASE.'/external-transactions', $data);
    }

    /** PUT /subscriptions/approve — 사용 시작 승인 */
    public function approveSubscription(string $token, ?string $accountMappingId = null): array
    {
        $data = $accountMappingId !== null ? ['accountMappingId' => $accountMappingId] : [];

        return $this->put(self::BASE.'/subscriptions/approve', $data, ['token' => $token]);
    }

    /**
     * PUT /subscriptions/{accountUid}/reject — 사용 시작 거절
     *
     * @param  array  $data  reason, comment
     */
    public function rejectSubscription(string $accountUid, array $data): array
    {
        return $this->put(self::BASE."/subscriptions/{$accountUid}/reject", $data);
    }

    /**
     * PUT /subscriptions/{accountUid}/unsubscription — 사용 중지
     *
     * @param  array  $data  reason, comment
     * @param  array  $query  refundType, amount
     */
    public function unsubscribe(string $accountUid, array $data, array $query = []): array
    {
        return $this->put(self::BASE."/subscriptions/{$accountUid}/unsubscription", $data, $query);
    }

    /** PUT /subscriptions/unsubscription/approve — 사용 해지 승인 */
    public function approveUnsubscription(string $accountUid): array
    {
        return $this->put(self::BASE.'/subscriptions/unsubscription/approve', [], ['accountUid' => $accountUid]);
    }
}
