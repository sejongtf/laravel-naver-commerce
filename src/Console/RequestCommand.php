<?php

namespace Sejongtf\LaravelNaverCommerce\Console;

use Illuminate\Console\Command;
use JsonException;
use Sejongtf\LaravelNaverCommerce\Console\Concerns\InteractsWithApi;
use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;
use Sejongtf\LaravelNaverCommerce\NaverCommerce;

/**
 * Call any API endpoint and print the JSON response.
 *
 * Mutating methods (anything other than GET) ask for confirmation unless --force is given.
 */
class RequestCommand extends Command
{
    use InteractsWithApi;

    protected $signature = 'naver-commerce:request
        {method : HTTP method (GET, POST, PUT, PATCH, DELETE)}
        {path : Endpoint path, e.g. /v1/seller/channels}
        {--query=* : Query parameter as key=value; repeat the key for array values}
        {--json= : JSON request body, or @path/to/file.json}
        {--seller= : Seller account ID; calls the API with a SELLER token}
        {--force : Skip the confirmation prompt for non-GET requests}';

    protected $description = 'Call an arbitrary Naver Commerce API endpoint and print the response';

    public function handle(NaverCommerce $api): int
    {
        $method = strtoupper((string) $this->argument('method'));
        $path = (string) $this->argument('path');

        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $this->components->error("Unsupported HTTP method [{$method}].");

            return self::INVALID;
        }

        try {
            $body = $this->parseBody();
        } catch (JsonException $e) {
            $this->components->error('Invalid JSON body: '.$e->getMessage());

            return self::INVALID;
        }

        if ($method !== 'GET' && ! $this->option('force')) {
            if (! $this->confirm("{$method} {$path} may modify seller data. Continue?")) {
                $this->components->warn('Aborted. Pass --force to skip this prompt.');

                return self::FAILURE;
            }
        }

        $options = ['query' => $this->parseQuery()];

        if ($body !== null) {
            $options['json'] = $body;
        }

        try {
            $response = $this->resolveApi($api)->request($method, $path, $options);
        } catch (NaverCommerceException $e) {
            $this->reportException($e);

            return self::FAILURE;
        }

        $decoded = $response->json();

        $this->line($decoded === null ? (string) $response->body() : $this->toJson($decoded));

        return self::SUCCESS;
    }

    /**
     * Turn repeated `--query key=value` options into a query array; repeated keys become arrays.
     */
    protected function parseQuery(): array
    {
        $query = [];

        foreach ((array) $this->option('query') as $pair) {
            [$key, $value] = array_pad(explode('=', (string) $pair, 2), 2, '');

            if ($key === '') {
                continue;
            }

            if (array_key_exists($key, $query)) {
                $query[$key] = array_merge((array) $query[$key], [$value]);
            } else {
                $query[$key] = $value;
            }
        }

        return $query;
    }

    /**
     * @throws JsonException
     */
    protected function parseBody(): ?array
    {
        $json = $this->option('json');

        if ($json === null || $json === '') {
            return null;
        }

        if (str_starts_with($json, '@')) {
            $file = substr($json, 1);

            if (! is_file($file)) {
                throw new JsonException("file not found: {$file}");
            }

            $json = (string) file_get_contents($file);
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [$decoded];
    }
}
