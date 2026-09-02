<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

use DateTimeInterface;

/**
 * 상품 문의(QnA) 및 고객 문의 API
 */
class Inquiries extends Resource
{
    // ----- 상품 문의 -----

    /**
     * GET /v1/contents/qnas — 상품 문의 목록 조회
     *
     * @param  array  $query  page, size, answered
     */
    public function qnas(DateTimeInterface|string $fromDate, DateTimeInterface|string $toDate, array $query = []): array
    {
        return $this->get('/v1/contents/qnas', ['fromDate' => $fromDate, 'toDate' => $toDate] + $query);
    }

    /** GET /v1/contents/qnas/templates — 상품 문의 답변 템플릿 목록 조회 */
    public function qnaTemplates(): array
    {
        return $this->get('/v1/contents/qnas/templates');
    }

    /** PUT /v1/contents/qnas/{questionId} — 상품 문의 답변 등록/수정 */
    public function answerQna(int $questionId, string $commentContent): array
    {
        return $this->put("/v1/contents/qnas/{$questionId}", ['commentContent' => $commentContent]);
    }

    // ----- 고객 문의 -----

    /**
     * GET /v1/pay-user/inquiries — 고객 문의 조회
     *
     * @param  string  $startSearchDate  yyyy-MM-dd
     * @param  string  $endSearchDate  yyyy-MM-dd
     * @param  array  $query  page, size, answered
     */
    public function customerInquiries(string $startSearchDate, string $endSearchDate, array $query = []): array
    {
        return $this->get('/v1/pay-user/inquiries', ['startSearchDate' => $startSearchDate, 'endSearchDate' => $endSearchDate] + $query);
    }

    /**
     * POST /v1/pay-merchant/inquiries/{inquiryNo}/answer — 고객 문의 답변 등록
     */
    public function answerCustomerInquiry(int $inquiryNo, string $answerComment, ?string $answerTemplateId = null): array
    {
        return $this->post("/v1/pay-merchant/inquiries/{$inquiryNo}/answer", $this->answerPayload($answerComment, $answerTemplateId));
    }

    /**
     * PUT /v1/pay-merchant/inquiries/{inquiryNo}/answer/{answerContentId} — 고객 문의 답변 수정
     */
    public function updateCustomerInquiryAnswer(int $inquiryNo, int $answerContentId, string $answerComment, ?string $answerTemplateId = null): array
    {
        return $this->put("/v1/pay-merchant/inquiries/{$inquiryNo}/answer/{$answerContentId}", $this->answerPayload($answerComment, $answerTemplateId));
    }

    protected function answerPayload(string $answerComment, ?string $answerTemplateId): array
    {
        $data = ['answerComment' => $answerComment];

        if ($answerTemplateId !== null) {
            $data['answerTemplateId'] = $answerTemplateId;
        }

        return $data;
    }
}
