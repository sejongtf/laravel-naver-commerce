<?php

namespace Sejongtf\LaravelNaverCommerce\Resources;

use Illuminate\Http\Client\Response;
use Sejongtf\LaravelNaverCommerce\Http\Client;

abstract class Resource
{
    public function __construct(protected Client $client) {}

    public function client(): Client
    {
        return $this->client;
    }

    protected function get(string $path, array $query = []): array
    {
        return $this->json($this->client->get($path, $query));
    }

    protected function post(string $path, array $data = [], array $query = []): array
    {
        return $this->json($this->client->post($path, $data, $query));
    }

    protected function put(string $path, array $data = [], array $query = []): array
    {
        return $this->json($this->client->put($path, $data, $query));
    }

    protected function patch(string $path, array $data = [], array $query = []): array
    {
        return $this->json($this->client->patch($path, $data, $query));
    }

    protected function delete(string $path, array $query = []): array
    {
        return $this->json($this->client->delete($path, $query));
    }

    protected function json(Response $response): array
    {
        $body = $response->json();

        return is_array($body) ? $body : [];
    }
}
