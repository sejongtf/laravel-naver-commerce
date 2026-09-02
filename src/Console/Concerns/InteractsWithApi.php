<?php

namespace Sejongtf\LaravelNaverCommerce\Console\Concerns;

use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;
use Sejongtf\LaravelNaverCommerce\NaverCommerce;

/**
 * Shared helpers for commands that call the API: --seller handling, error reporting, JSON output.
 */
trait InteractsWithApi
{
    /**
     * Apply the --seller option (SELLER token) to the API instance.
     */
    protected function resolveApi(NaverCommerce $api): NaverCommerce
    {
        $accountId = $this->option('seller');

        return $accountId ? $api->forSeller($accountId) : $api;
    }

    /**
     * Print an API exception (message, trace ID, error body) to the console.
     */
    protected function reportException(NaverCommerceException $e): void
    {
        $this->components->error($e->getMessage());

        if ($e->traceId()) {
            $this->components->twoColumnDetail('Trace ID', $e->traceId());
        }

        $errors = $e->errors();

        if ($errors !== []) {
            $this->line($this->toJson($errors));
        }
    }

    protected function toJson(mixed $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
