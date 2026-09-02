<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 정산 API (/v1/pay-settle)
 */
class Settlements extends Resource
{
    protected const BASE = '/v1/pay-settle';

    /**
     * GET /settle/daily — 일별 정산 내역 조회
     *
     * @param  string  $startDate  yyyy-MM-dd
     * @param  string  $endDate  yyyy-MM-dd
     */
    public function daily(string $startDate, string $endDate, int $pageNumber = 1, int $pageSize = 100): array
    {
        return $this->get(self::BASE.'/settle/daily', compact('startDate', 'endDate', 'pageNumber', 'pageSize'));
    }

    /**
     * GET /settle/case — 건별 정산 내역 조회
     *
     * @param  array  $query  searchDate, orderId, productOrderId, periodType, settleDecisionType, settleType
     */
    public function cases(array $query = [], int $pageNumber = 1, int $pageSize = 100): array
    {
        return $this->get(self::BASE.'/settle/case', $query + compact('pageNumber', 'pageSize'));
    }

    /**
     * GET /settle/commission-details — 수수료 상세 내역 조회
     *
     * @param  array  $query  searchDate, orderId, productOrderId, periodType, settleDecisionType, settleType
     */
    public function commissionDetails(array $query = [], int $pageNumber = 1, int $pageSize = 100): array
    {
        return $this->get(self::BASE.'/settle/commission-details', $query + compact('pageNumber', 'pageSize'));
    }

    /** GET /vat/daily — 일별 부가세 내역 조회 */
    public function vatDaily(string $startDate, string $endDate, int $pageNumber = 1, int $pageSize = 100): array
    {
        return $this->get(self::BASE.'/vat/daily', compact('startDate', 'endDate', 'pageNumber', 'pageSize'));
    }

    /** GET /vat/case — 건별 부가세 내역 조회 */
    public function vatCases(string $startDate, string $endDate, int $pageNumber = 1, int $pageSize = 100): array
    {
        return $this->get(self::BASE.'/vat/case', compact('startDate', 'endDate', 'pageNumber', 'pageSize'));
    }
}
