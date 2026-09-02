<?php

namespace Sejongtf\LaravelNaverCommerce\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Sejongtf\LaravelNaverCommerce\NaverCommerce forSeller(string $accountId)
 * @method static \Sejongtf\LaravelNaverCommerce\NaverCommerce asSelf()
 * @method static \Sejongtf\LaravelNaverCommerce\Http\Client client()
 * @method static string token()
 * @method static \Illuminate\Http\Client\Response|null lastResponse()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Orders orders()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Products products()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\GroupProducts groupProducts()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Categories categories()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Catalog catalog()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\DeliveryInfo deliveryInfo()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\FashionModels fashionModels()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\SellerNotices sellerNotices()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Inquiries inquiries()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Settlements settlements()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Logistics logistics()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\Seller seller()
 * @method static \Sejongtf\LaravelNaverCommerce\Resources\CommerceSolutions commerceSolutions()
 * @method static array get(string $path, array $query = [])
 * @method static array post(string $path, array $data = [], array $query = [])
 * @method static array put(string $path, array $data = [], array $query = [])
 * @method static array patch(string $path, array $data = [], array $query = [])
 * @method static array delete(string $path, array $query = [])
 * @method static \Illuminate\Http\Client\Response request(string $method, string $path, array $options = [])
 *
 * @see \Sejongtf\LaravelNaverCommerce\NaverCommerce
 */
class NaverCommerce extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Sejongtf\LaravelNaverCommerce\NaverCommerce::class;
    }
}
