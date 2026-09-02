<?php

namespace Sejongtf\LaravelNaverCommerce;

use Illuminate\Http\Client\Response;
use Sejongtf\LaravelNaverCommerce\Http\Client;
use Sejongtf\LaravelNaverCommerce\Resources\Catalog;
use Sejongtf\LaravelNaverCommerce\Resources\Categories;
use Sejongtf\LaravelNaverCommerce\Resources\CommerceSolutions;
use Sejongtf\LaravelNaverCommerce\Resources\DeliveryInfo;
use Sejongtf\LaravelNaverCommerce\Resources\FashionModels;
use Sejongtf\LaravelNaverCommerce\Resources\GroupProducts;
use Sejongtf\LaravelNaverCommerce\Resources\Inquiries;
use Sejongtf\LaravelNaverCommerce\Resources\Logistics;
use Sejongtf\LaravelNaverCommerce\Resources\Orders;
use Sejongtf\LaravelNaverCommerce\Resources\Products;
use Sejongtf\LaravelNaverCommerce\Resources\Seller;
use Sejongtf\LaravelNaverCommerce\Resources\SellerNotices;
use Sejongtf\LaravelNaverCommerce\Resources\Settlements;

/**
 * @phpstan-consistent-constructor
 */
class NaverCommerce
{
    public function __construct(protected Client $client) {}

    /**
     * 위임받은 판매자(SELLER 토큰) 리소스를 호출하는 인스턴스.
     */
    public function forSeller(string $accountId): static
    {
        return new static($this->client->forSeller($accountId));
    }

    /**
     * 자기 애플리케이션(SELF 토큰) 리소스를 호출하는 인스턴스.
     */
    public function asSelf(): static
    {
        return new static($this->client->asSelf());
    }

    public function client(): Client
    {
        return $this->client;
    }

    /**
     * 현재 컨텍스트의 액세스 토큰.
     */
    public function token(): string
    {
        return $this->client->tokens()->token($this->client->tokenType(), $this->client->accountId());
    }

    public function lastResponse(): ?Response
    {
        return $this->client->lastResponse();
    }

    // ----- 리소스 -----

    public function orders(): Orders
    {
        return new Orders($this->client);
    }

    public function products(): Products
    {
        return new Products($this->client);
    }

    public function groupProducts(): GroupProducts
    {
        return new GroupProducts($this->client);
    }

    public function categories(): Categories
    {
        return new Categories($this->client);
    }

    public function catalog(): Catalog
    {
        return new Catalog($this->client);
    }

    public function deliveryInfo(): DeliveryInfo
    {
        return new DeliveryInfo($this->client);
    }

    public function fashionModels(): FashionModels
    {
        return new FashionModels($this->client);
    }

    public function sellerNotices(): SellerNotices
    {
        return new SellerNotices($this->client);
    }

    public function inquiries(): Inquiries
    {
        return new Inquiries($this->client);
    }

    public function settlements(): Settlements
    {
        return new Settlements($this->client);
    }

    public function logistics(): Logistics
    {
        return new Logistics($this->client);
    }

    public function seller(): Seller
    {
        return new Seller($this->client);
    }

    public function commerceSolutions(): CommerceSolutions
    {
        return new CommerceSolutions($this->client);
    }

    // ----- 범용 호출(미래 엔드포인트용) -----

    public function get(string $path, array $query = []): array
    {
        return $this->toArray($this->client->get($path, $query));
    }

    public function post(string $path, array $data = [], array $query = []): array
    {
        return $this->toArray($this->client->post($path, $data, $query));
    }

    public function put(string $path, array $data = [], array $query = []): array
    {
        return $this->toArray($this->client->put($path, $data, $query));
    }

    public function patch(string $path, array $data = [], array $query = []): array
    {
        return $this->toArray($this->client->patch($path, $data, $query));
    }

    public function delete(string $path, array $query = []): array
    {
        return $this->toArray($this->client->delete($path, $query));
    }

    public function request(string $method, string $path, array $options = []): Response
    {
        return $this->client->request($method, $path, $options);
    }

    protected function toArray(Response $response): array
    {
        $body = $response->json();

        return is_array($body) ? $body : [];
    }
}
