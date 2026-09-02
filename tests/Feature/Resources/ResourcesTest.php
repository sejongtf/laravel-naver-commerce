<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Sejongtf\LaravelNaverCommerce\Facades\NaverCommerce;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

/** @var TestCase $this */

/**
 * 각 리소스 메서드가 올바른 HTTP 메서드·경로·쿼리·본문을 만드는지 테이블 방식으로 검증한다.
 *
 * [호출 클로저, 기대 메서드, 기대 URL(경로+쿼리), 기대 JSON 본문(null 이면 검사 안 함)]
 */
dataset('resource calls', [
    // Orders
    'orders.productOrderIds' => [fn () => NaverCommerce::orders()->productOrderIds('O1'), 'GET', '/v1/pay-order/seller/orders/O1/product-order-ids', null],
    'orders.lastChangedStatuses' => [fn () => NaverCommerce::orders()->lastChangedStatuses('2024-01-01T00:00:00.000+09:00', ['limitCount' => 300]), 'GET', '/v1/pay-order/seller/product-orders/last-changed-statuses?lastChangedFrom=2024-01-01T00%3A00%3A00.000%2B09%3A00&limitCount=300', null],
    'orders.query' => [fn () => NaverCommerce::orders()->query(['P1', 'P2'], true), 'POST', '/v1/pay-order/seller/product-orders/query', ['productOrderIds' => ['P1', 'P2'], 'quantityClaimCompatibility' => true]],
    'orders.confirm' => [fn () => NaverCommerce::orders()->confirm(['P1']), 'POST', '/v1/pay-order/seller/product-orders/confirm', ['productOrderIds' => ['P1']]],
    'orders.dispatch' => [fn () => NaverCommerce::orders()->dispatch([['productOrderId' => 'P1', 'deliveryMethod' => 'DELIVERY', 'deliveryCompanyCode' => 'CJGLS', 'trackingNumber' => '123']]), 'POST', '/v1/pay-order/seller/product-orders/dispatch', ['dispatchProductOrders' => [['productOrderId' => 'P1', 'deliveryMethod' => 'DELIVERY', 'deliveryCompanyCode' => 'CJGLS', 'trackingNumber' => '123']]]],
    'orders.delay' => [fn () => NaverCommerce::orders()->delay('P1', ['delayedDispatchReason' => 'PRODUCT_PREPARE']), 'POST', '/v1/pay-order/seller/product-orders/P1/delay', ['delayedDispatchReason' => 'PRODUCT_PREPARE']],
    'orders.changeHopeDelivery' => [fn () => NaverCommerce::orders()->changeHopeDelivery('P1', ['hopeDeliveryYmd' => '20240101', 'changeReason' => 'r']), 'POST', '/v1/pay-order/seller/product-orders/P1/hope-delivery/change', ['hopeDeliveryYmd' => '20240101', 'changeReason' => 'r']],
    'orders.requestCancel' => [fn () => NaverCommerce::orders()->requestCancel('P1', ['cancelReason' => 'SOLD_OUT']), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/cancel/request', ['cancelReason' => 'SOLD_OUT']],
    'orders.approveCancel' => [fn () => NaverCommerce::orders()->approveCancel('P1'), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/cancel/approve', []],
    'orders.requestReturn' => [fn () => NaverCommerce::orders()->requestReturn('P1', ['returnReason' => 'x', 'collectDeliveryMethod' => 'y']), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/return/request', ['returnReason' => 'x', 'collectDeliveryMethod' => 'y']],
    'orders.approveReturn' => [fn () => NaverCommerce::orders()->approveReturn('P1'), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/return/approve', []],
    'orders.rejectReturn' => [fn () => NaverCommerce::orders()->rejectReturn('P1', 'reason'), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/return/reject', ['rejectReturnReason' => 'reason']],
    'orders.holdbackReturn' => [fn () => NaverCommerce::orders()->holdbackReturn('P1', ['holdbackClassType' => 'A', 'holdbackReturnDetailReason' => 'B']), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/return/holdback', ['holdbackClassType' => 'A', 'holdbackReturnDetailReason' => 'B']],
    'orders.releaseReturnHoldback' => [fn () => NaverCommerce::orders()->releaseReturnHoldback('P1'), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/return/holdback/release', []],
    'orders.approveExchangeCollect' => [fn () => NaverCommerce::orders()->approveExchangeCollect('P1'), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/exchange/collect/approve', []],
    'orders.dispatchExchange' => [fn () => NaverCommerce::orders()->dispatchExchange('P1', ['reDeliveryMethod' => 'DELIVERY']), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/exchange/dispatch', ['reDeliveryMethod' => 'DELIVERY']],
    'orders.rejectExchange' => [fn () => NaverCommerce::orders()->rejectExchange('P1', 'r'), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/exchange/reject', ['rejectExchangeReason' => 'r']],
    'orders.holdbackExchange' => [fn () => NaverCommerce::orders()->holdbackExchange('P1', ['holdbackClassType' => 'A', 'holdbackExchangeDetailReason' => 'B']), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/exchange/holdback', ['holdbackClassType' => 'A', 'holdbackExchangeDetailReason' => 'B']],
    'orders.releaseExchangeHoldback' => [fn () => NaverCommerce::orders()->releaseExchangeHoldback('P1'), 'POST', '/v1/pay-order/seller/product-orders/P1/claim/exchange/holdback/release', []],

    // Products
    'products.create' => [fn () => NaverCommerce::products()->create(['originProduct' => ['name' => 'n']]), 'POST', '/v2/products', ['originProduct' => ['name' => 'n']]],
    'products.search' => [fn () => NaverCommerce::products()->search(['page' => 1, 'size' => 50]), 'POST', '/v1/products/search', ['page' => 1, 'size' => 50]],
    'products.getOrigin' => [fn () => NaverCommerce::products()->getOrigin(1), 'GET', '/v2/products/origin-products/1', null],
    'products.updateOrigin' => [fn () => NaverCommerce::products()->updateOrigin(1, ['originProduct' => []]), 'PUT', '/v2/products/origin-products/1', ['originProduct' => []]],
    'products.deleteOrigin' => [fn () => NaverCommerce::products()->deleteOrigin(1), 'DELETE', '/v2/products/origin-products/1', null],
    'products.getChannel' => [fn () => NaverCommerce::products()->getChannel(2), 'GET', '/v2/products/channel-products/2', null],
    'products.updateChannel' => [fn () => NaverCommerce::products()->updateChannel(2, ['originProduct' => []]), 'PUT', '/v2/products/channel-products/2', ['originProduct' => []]],
    'products.deleteChannel' => [fn () => NaverCommerce::products()->deleteChannel(2), 'DELETE', '/v2/products/channel-products/2', null],
    'products.changeStatus' => [fn () => NaverCommerce::products()->changeStatus(1, ['statusType' => 'SALE']), 'PUT', '/v1/products/origin-products/1/change-status', ['statusType' => 'SALE']],
    'products.updateOptionStock' => [fn () => NaverCommerce::products()->updateOptionStock(1, ['optionInfo' => []]), 'PUT', '/v1/products/origin-products/1/option-stock', ['optionInfo' => []]],
    'products.bulkUpdate' => [fn () => NaverCommerce::products()->bulkUpdate(['originProductNos' => [1]]), 'PUT', '/v1/products/origin-products/bulk-update', ['originProductNos' => [1]]],
    'products.multiUpdate' => [fn () => NaverCommerce::products()->multiUpdate([['originProductNo' => 1, 'multiUpdateTypes' => ['STOCK_QUANTITY'], 'stockQuantity' => 3]]), 'PATCH', '/v1/products/origin-products/multi-update', ['multiProductUpdateRequestVos' => [['originProductNo' => 1, 'multiUpdateTypes' => ['STOCK_QUANTITY'], 'stockQuantity' => 3]]]],
    'products.applyNotice' => [fn () => NaverCommerce::products()->applyNotice([1, 2], 9), 'PUT', '/v1/products/channel-products/notice/apply', ['channelProductNos' => [1, 2], 'sellerNoticeId' => 9]],
    'products.inspections' => [fn () => NaverCommerce::products()->inspections(['page' => 1]), 'GET', '/v1/product-inspections/channel-products?page=1', null],
    'products.restoreInspection' => [fn () => NaverCommerce::products()->restoreInspection(5), 'PUT', '/v1/product-inspections/channel-product/5/restore', null],

    // GroupProducts
    'groupProducts.create' => [fn () => NaverCommerce::groupProducts()->create(['groupProduct' => []]), 'POST', '/v2/standard-group-products', ['groupProduct' => []]],
    'groupProducts.find' => [fn () => NaverCommerce::groupProducts()->find(7), 'GET', '/v2/standard-group-products/7', null],
    'groupProducts.update' => [fn () => NaverCommerce::groupProducts()->update(7, ['groupProduct' => []]), 'PUT', '/v2/standard-group-products/7', ['groupProduct' => []]],
    'groupProducts.destroy' => [fn () => NaverCommerce::groupProducts()->destroy(7), 'DELETE', '/v2/standard-group-products/7', null],
    'groupProducts.status' => [fn () => NaverCommerce::groupProducts()->status(['type' => 'CREATE', 'requestId' => 'r']), 'GET', '/v2/standard-group-products/status?type=CREATE&requestId=r', null],
    'groupProducts.convert' => [fn () => NaverCommerce::groupProducts()->convert(['groupProduct' => []]), 'POST', '/v2/standard-group-products/convert-products', ['groupProduct' => []]],
    'groupProducts.validateConversion' => [fn () => NaverCommerce::groupProducts()->validateConversion([1, 2]), 'POST', '/v2/standard-group-products/validate-conversion', ['originProductNos' => [1, 2]]],
    'groupProducts.release' => [fn () => NaverCommerce::groupProducts()->release(['targets' => [1]]), 'POST', '/v2/standard-group-products/release-group', ['targets' => [1]]],
    'groupProducts.saveTempDetailContent' => [fn () => NaverCommerce::groupProducts()->saveTempDetailContent('<p>x</p>', 3), 'POST', '/v2/standard-group-products/temp-detail-content', ['content' => '<p>x</p>', 'detailContentTempId' => 3]],

    // Categories
    'categories.all' => [fn () => NaverCommerce::categories()->all(true), 'GET', '/v1/categories?last=true', null],
    'categories.all.noParam' => [fn () => NaverCommerce::categories()->all(), 'GET', '/v1/categories', null],
    'categories.find' => [fn () => NaverCommerce::categories()->find('50000000'), 'GET', '/v1/categories/50000000', null],
    'categories.subCategories' => [fn () => NaverCommerce::categories()->subCategories('50000000'), 'GET', '/v1/categories/50000000/sub-categories', null],
    'categories.attributes' => [fn () => NaverCommerce::categories()->attributes('c'), 'GET', '/v1/product-attributes/attributes?categoryId=c', null],
    'categories.attributeValues' => [fn () => NaverCommerce::categories()->attributeValues('c'), 'GET', '/v1/product-attributes/attribute-values?categoryId=c', null],
    'categories.attributeValueUnits' => [fn () => NaverCommerce::categories()->attributeValueUnits(), 'GET', '/v1/product-attributes/attribute-value-units', null],
    'categories.standardOptions' => [fn () => NaverCommerce::categories()->standardOptions('c'), 'GET', '/v1/options/standard-options?categoryId=c', null],
    'categories.purchaseOptionGuides' => [fn () => NaverCommerce::categories()->purchaseOptionGuides('c'), 'GET', '/v2/standard-purchase-option-guides?categoryId=c', null],
    'categories.providedNoticeTypes' => [fn () => NaverCommerce::categories()->providedNoticeTypes('c'), 'GET', '/v1/products-for-provided-notice?categoryId=c', null],
    'categories.providedNoticeType' => [fn () => NaverCommerce::categories()->providedNoticeType('WEAR'), 'GET', '/v1/products-for-provided-notice/WEAR', null],

    // Catalog
    'catalog.brands' => [fn () => NaverCommerce::catalog()->brands('나이키'), 'GET', '/v1/product-brands?name=%EB%82%98%EC%9D%B4%ED%82%A4', null],
    'catalog.manufacturers' => [fn () => NaverCommerce::catalog()->manufacturers('m'), 'GET', '/v1/product-manufacturers?name=m', null],
    'catalog.models' => [fn () => NaverCommerce::catalog()->models('m', ['page' => 1]), 'GET', '/v1/product-models?name=m&page=1', null],
    'catalog.model' => [fn () => NaverCommerce::catalog()->model(3), 'GET', '/v1/product-models/3', null],
    'catalog.originAreas' => [fn () => NaverCommerce::catalog()->originAreas(), 'GET', '/v1/product-origin-areas', null],
    'catalog.searchOriginAreas' => [fn () => NaverCommerce::catalog()->searchOriginAreas(['name' => 'x']), 'GET', '/v1/product-origin-areas/query?name=x', null],
    'catalog.subOriginAreas' => [fn () => NaverCommerce::catalog()->subOriginAreas('01'), 'GET', '/v1/product-origin-areas/sub-origin-areas?code=01', null],
    'catalog.sizes' => [fn () => NaverCommerce::catalog()->sizes(), 'GET', '/v1/product-sizes', null],
    'catalog.size' => [fn () => NaverCommerce::catalog()->size(4), 'GET', '/v1/product-sizes/4', null],
    'catalog.recommendTags' => [fn () => NaverCommerce::catalog()->recommendTags('k'), 'GET', '/v2/tags/recommend-tags?keyword=k', null],
    'catalog.restrictedTags' => [fn () => NaverCommerce::catalog()->restrictedTags(['a', 'b']), 'GET', '/v2/tags/restricted-tags?tags=a&tags=b', null],

    // DeliveryInfo
    'deliveryInfo.bundleGroups' => [fn () => NaverCommerce::deliveryInfo()->bundleGroups(['usable' => true]), 'GET', '/v1/product-delivery-info/bundle-groups?usable=true', null],
    'deliveryInfo.bundleGroup' => [fn () => NaverCommerce::deliveryInfo()->bundleGroup(1), 'GET', '/v1/product-delivery-info/bundle-groups/1', null],
    'deliveryInfo.createBundleGroup' => [fn () => NaverCommerce::deliveryInfo()->createBundleGroup(['name' => 'g']), 'POST', '/v1/product-delivery-info/bundle-groups', ['deliveryBundleGroup' => ['name' => 'g']]],
    'deliveryInfo.createBundleGroup.wrapped' => [fn () => NaverCommerce::deliveryInfo()->createBundleGroup(['deliveryBundleGroup' => ['name' => 'g']]), 'POST', '/v1/product-delivery-info/bundle-groups', ['deliveryBundleGroup' => ['name' => 'g']]],
    'deliveryInfo.updateBundleGroup' => [fn () => NaverCommerce::deliveryInfo()->updateBundleGroup(1, ['name' => 'g']), 'PUT', '/v1/product-delivery-info/bundle-groups/1', ['deliveryBundleGroup' => ['name' => 'g']]],
    'deliveryInfo.hopeDeliveryGroups' => [fn () => NaverCommerce::deliveryInfo()->hopeDeliveryGroups(), 'GET', '/v1/product-delivery-info/hope-delivery-groups', null],
    'deliveryInfo.hopeDeliveryGroup' => [fn () => NaverCommerce::deliveryInfo()->hopeDeliveryGroup(1), 'GET', '/v1/product-delivery-info/hope-delivery-groups/1', null],
    'deliveryInfo.createHopeDeliveryGroup' => [fn () => NaverCommerce::deliveryInfo()->createHopeDeliveryGroup(['name' => 'h']), 'POST', '/v1/product-delivery-info/hope-delivery-groups', ['hopeDeliveryGroup' => ['name' => 'h']]],
    'deliveryInfo.updateHopeDeliveryGroup' => [fn () => NaverCommerce::deliveryInfo()->updateHopeDeliveryGroup(1, ['name' => 'h']), 'PUT', '/v1/product-delivery-info/hope-delivery-groups/1', ['hopeDeliveryGroup' => ['name' => 'h']]],
    'deliveryInfo.returnDeliveryCompanies' => [fn () => NaverCommerce::deliveryInfo()->returnDeliveryCompanies('CJ'), 'GET', '/v2/product-delivery-info/return-delivery-companies?name=CJ', null],

    // FashionModels
    'fashionModels.all' => [fn () => NaverCommerce::fashionModels()->all(), 'GET', '/v1/product-fashion-models', null],
    'fashionModels.create' => [fn () => NaverCommerce::fashionModels()->create(['name' => 'm']), 'POST', '/v1/product-fashion-models', ['name' => 'm']],
    'fashionModels.update' => [fn () => NaverCommerce::fashionModels()->update(1, ['name' => 'm']), 'PUT', '/v1/product-fashion-models/1', ['name' => 'm']],
    'fashionModels.destroy' => [fn () => NaverCommerce::fashionModels()->destroy(1), 'DELETE', '/v1/product-fashion-models/1', null],

    // SellerNotices
    'sellerNotices.list' => [fn () => NaverCommerce::sellerNotices()->list(['page' => 1, 'size' => 10]), 'GET', '/v1/contents/seller-notices?page=1&size=10', null],
    'sellerNotices.find' => [fn () => NaverCommerce::sellerNotices()->find(1), 'GET', '/v1/contents/seller-notices/1', null],
    'sellerNotices.create' => [fn () => NaverCommerce::sellerNotices()->create(['title' => 't', 'detailContents' => 'd']), 'POST', '/v1/contents/seller-notices', ['title' => 't', 'detailContents' => 'd']],
    'sellerNotices.update' => [fn () => NaverCommerce::sellerNotices()->update(1, ['title' => 't', 'detailContents' => 'd']), 'PUT', '/v1/contents/seller-notices/1', ['title' => 't', 'detailContents' => 'd']],
    'sellerNotices.destroy' => [fn () => NaverCommerce::sellerNotices()->destroy(1), 'DELETE', '/v1/contents/seller-notices/1', null],

    // Inquiries
    'inquiries.qnas' => [fn () => NaverCommerce::inquiries()->qnas('2024-01-01T00:00:00.000+09:00', '2024-01-02T00:00:00.000+09:00', ['answered' => false]), 'GET', '/v1/contents/qnas?fromDate=2024-01-01T00%3A00%3A00.000%2B09%3A00&toDate=2024-01-02T00%3A00%3A00.000%2B09%3A00&answered=false', null],
    'inquiries.qnaTemplates' => [fn () => NaverCommerce::inquiries()->qnaTemplates(), 'GET', '/v1/contents/qnas/templates', null],
    'inquiries.answerQna' => [fn () => NaverCommerce::inquiries()->answerQna(11, '답변'), 'PUT', '/v1/contents/qnas/11', ['commentContent' => '답변']],
    'inquiries.customerInquiries' => [fn () => NaverCommerce::inquiries()->customerInquiries('2024-01-01', '2024-01-31', ['page' => 1]), 'GET', '/v1/pay-user/inquiries?startSearchDate=2024-01-01&endSearchDate=2024-01-31&page=1', null],
    'inquiries.answerCustomerInquiry' => [fn () => NaverCommerce::inquiries()->answerCustomerInquiry(5, 'a', 't1'), 'POST', '/v1/pay-merchant/inquiries/5/answer', ['answerComment' => 'a', 'answerTemplateId' => 't1']],
    'inquiries.updateCustomerInquiryAnswer' => [fn () => NaverCommerce::inquiries()->updateCustomerInquiryAnswer(5, 6, 'a'), 'PUT', '/v1/pay-merchant/inquiries/5/answer/6', ['answerComment' => 'a']],

    // Settlements
    'settlements.daily' => [fn () => NaverCommerce::settlements()->daily('2024-01-01', '2024-01-31', 2, 50), 'GET', '/v1/pay-settle/settle/daily?startDate=2024-01-01&endDate=2024-01-31&pageNumber=2&pageSize=50', null],
    'settlements.cases' => [fn () => NaverCommerce::settlements()->cases(['orderId' => 'O1']), 'GET', '/v1/pay-settle/settle/case?orderId=O1&pageNumber=1&pageSize=100', null],
    'settlements.commissionDetails' => [fn () => NaverCommerce::settlements()->commissionDetails(['searchDate' => '2024-01-01', 'periodType' => 'SETTLE']), 'GET', '/v1/pay-settle/settle/commission-details?searchDate=2024-01-01&periodType=SETTLE&pageNumber=1&pageSize=100', null],
    'settlements.vatDaily' => [fn () => NaverCommerce::settlements()->vatDaily('2024-01-01', '2024-01-31'), 'GET', '/v1/pay-settle/vat/daily?startDate=2024-01-01&endDate=2024-01-31&pageNumber=1&pageSize=100', null],
    'settlements.vatCases' => [fn () => NaverCommerce::settlements()->vatCases('2024-01-01', '2024-01-31'), 'GET', '/v1/pay-settle/vat/case?startDate=2024-01-01&endDate=2024-01-31&pageNumber=1&pageSize=100', null],

    // Logistics
    'logistics.companies' => [fn () => NaverCommerce::logistics()->companies(), 'GET', '/v1/logistics/logistics-companies', null],
    'logistics.outboundLocations' => [fn () => NaverCommerce::logistics()->outboundLocations(), 'GET', '/v1/logistics/outbound-locations', null],
    'logistics.sku' => [fn () => NaverCommerce::logistics()->sku('NS1'), 'GET', '/v2/logistics/products/sellers/me/skus/NS1', null],
    'logistics.skuLegacy' => [fn () => NaverCommerce::logistics()->skuLegacy('NS1'), 'GET', '/v1/logistics/products/sellers/me/skus/NS1', null],
    'logistics.skuProductMappings' => [fn () => NaverCommerce::logistics()->skuProductMappings('NS1', ['page' => 1]), 'GET', '/v1/logistics/products/sellers/me/skus/NS1/product-mappings?page=1', null],
    'logistics.searchSkus' => [fn () => NaverCommerce::logistics()->searchSkus(['nsIds' => ['NS1']]), 'POST', '/v1/logistics/products/sellers/me/skus/query-paged-list', ['nsIds' => ['NS1']]],

    // Seller
    'seller.account' => [fn () => NaverCommerce::seller()->account(), 'GET', '/v1/seller/account', null],
    'seller.channels' => [fn () => NaverCommerce::seller()->channels(), 'GET', '/v1/seller/channels', null],
    'seller.addressBooks' => [fn () => NaverCommerce::seller()->addressBooks(2), 'GET', '/v1/seller/addressbooks-for-page?page=2', null],
    'seller.addressBook' => [fn () => NaverCommerce::seller()->addressBook(3), 'GET', '/v1/seller/addressbooks/3', null],
    'seller.thisDayDispatch' => [fn () => NaverCommerce::seller()->thisDayDispatch(), 'GET', '/v1/seller/this-day-dispatch', null],
    'seller.setThisDayDispatch' => [fn () => NaverCommerce::seller()->setThisDayDispatch(['basisHour' => 13, 'basisMinute' => 0, 'reason' => 'r']), 'POST', '/v1/seller/this-day-dispatch', ['basisHour' => 13, 'basisMinute' => 0, 'reason' => 'r']],

    // CommerceSolutions
    'commerceSolutions.sellerInfoByToken' => [fn () => NaverCommerce::commerceSolutions()->sellerInfoByToken('jwe'), 'GET', '/v1/commerce-solutions/seller-info-by-token?token=jwe', null],
    'commerceSolutions.subscription' => [fn () => NaverCommerce::commerceSolutions()->subscription('U1'), 'GET', '/v1/commerce-solutions/subscriptions/U1', null],
    'commerceSolutions.transactions' => [fn () => NaverCommerce::commerceSolutions()->transactions(['paymentConfirmStartDate' => '2024-01-01T00:00:00.000+09:00', 'paymentConfirmEndDate' => '2024-01-02T00:00:00.000+09:00']), 'GET', '/v1/commerce-solutions/transactions?paymentConfirmStartDate=2024-01-01T00%3A00%3A00.000%2B09%3A00&paymentConfirmEndDate=2024-01-02T00%3A00%3A00.000%2B09%3A00', null],
    'commerceSolutions.sendExternalTransaction' => [fn () => NaverCommerce::commerceSolutions()->sendExternalTransaction(['paymentId' => 'p']), 'POST', '/v1/commerce-solutions/external-transactions', ['paymentId' => 'p']],
    'commerceSolutions.approveSubscription' => [fn () => NaverCommerce::commerceSolutions()->approveSubscription('tok', 'map-1'), 'PUT', '/v1/commerce-solutions/subscriptions/approve?token=tok', ['accountMappingId' => 'map-1']],
    'commerceSolutions.rejectSubscription' => [fn () => NaverCommerce::commerceSolutions()->rejectSubscription('U1', ['reason' => 'r', 'comment' => 'c']), 'PUT', '/v1/commerce-solutions/subscriptions/U1/reject', ['reason' => 'r', 'comment' => 'c']],
    'commerceSolutions.unsubscribe' => [fn () => NaverCommerce::commerceSolutions()->unsubscribe('U1', ['reason' => 'r', 'comment' => 'c'], ['refundType' => 'FULL', 'amount' => 1000]), 'PUT', '/v1/commerce-solutions/subscriptions/U1/unsubscription?refundType=FULL&amount=1000', ['reason' => 'r', 'comment' => 'c']],
    'commerceSolutions.approveUnsubscription' => [fn () => NaverCommerce::commerceSolutions()->approveUnsubscription('U1'), 'PUT', '/v1/commerce-solutions/subscriptions/unsubscription/approve?accountUid=U1', []],
]);

it('maps resource methods to the correct request', function (Closure $call, string $method, string $expectedUrl, ?array $expectedBody) {
    $this->fakeApi(['*' => Http::response(['ok' => true])]);

    expect($call())->toBe(['ok' => true]);

    $this->assertApiSent(function (Request $request) use ($method, $expectedUrl, $expectedBody) {
        if ($request->method() !== $method || $request->url() !== $this->url($expectedUrl)) {
            return false;
        }

        if ($expectedBody === null) {
            return true;
        }

        return $request->data() === $expectedBody;
    });
})->with('resource calls');

it('uploads images as multipart with repeated imageFiles parts', function () {
    $this->fakeApi([$this->url('/v1/product-images/upload') => Http::response(['images' => [['url' => 'https://img/1.jpg'], ['url' => 'https://img/2.jpg']]])]);

    $tmp = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($tmp, 'fake-jpg');

    $result = NaverCommerce::products()->uploadImages([
        $tmp,
        ['contents' => 'png-bytes', 'filename' => 'two.png'],
    ]);

    expect($result['images'])->toHaveCount(2);

    $this->assertApiSent(function (Request $request) use ($tmp) {
        return $request->method() === 'POST'
            && $request->isMultipart()
            && $request->hasFile('imageFiles', null, basename($tmp))
            && $request->hasFile('imageFiles', 'png-bytes', 'two.png');
    });

    @unlink($tmp);
});

it('rejects more than 10 images', function () {
    NaverCommerce::products()->uploadImages(array_fill(0, 11, ['contents' => 'x']));
})->throws(InvalidArgumentException::class);

it('rejects a missing image path', function () {
    NaverCommerce::products()->uploadImages(['/nonexistent/file.jpg']);
})->throws(InvalidArgumentException::class);
