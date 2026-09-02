<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

/**
 * 판매자정보 API (/v1/seller)
 */
class Seller extends Resource
{
    protected const BASE = '/v1/seller';

    /** GET /account — 계정 정보 조회 */
    public function account(): array
    {
        return $this->get(self::BASE.'/account');
    }

    /** GET /channels — 계정으로 채널 정보 조회 */
    public function channels(): array
    {
        return $this->get(self::BASE.'/channels');
    }

    /** GET /addressbooks-for-page — 주소록 목록 조회 */
    public function addressBooks(?int $page = null): array
    {
        return $this->get(self::BASE.'/addressbooks-for-page', ['page' => $page]);
    }

    /** GET /addressbooks/{no} — 주소록 단건 조회 */
    public function addressBook(int $addressBookNo): array
    {
        return $this->get(self::BASE."/addressbooks/{$addressBookNo}");
    }

    /** GET /this-day-dispatch — 오늘출발 설정 정보 조회 */
    public function thisDayDispatch(): array
    {
        return $this->get(self::BASE.'/this-day-dispatch');
    }

    /**
     * POST /this-day-dispatch — 오늘출발 정보 설정
     *
     * @param  array  $data  basisHour, basisMinute, reason(필수), holidayOfTheWeek, sellerHolidays[]
     */
    public function setThisDayDispatch(array $data): array
    {
        return $this->post(self::BASE.'/this-day-dispatch', $data);
    }
}
