<?php

namespace Sejongtf\LaravelNaverCommerce\Console;

use Illuminate\Console\Command;
use Sejongtf\LaravelNaverCommerce\Console\Concerns\InteractsWithApi;
use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;
use Sejongtf\LaravelNaverCommerce\NaverCommerce;

/**
 * Smoke-test credentials by fetching the seller account and channels (read-only).
 */
class PingCommand extends Command
{
    use InteractsWithApi;

    protected $signature = 'naver-commerce:ping
        {--seller= : Seller account ID; calls the API with a SELLER token}';

    protected $description = 'Verify Naver Commerce API credentials by fetching the seller account';

    public function handle(NaverCommerce $api): int
    {
        $api = $this->resolveApi($api);

        try {
            $account = $api->seller()->account();
            $channels = $api->seller()->channels();
        } catch (NaverCommerceException $e) {
            $this->reportException($e);

            return self::FAILURE;
        }

        $response = $api->lastResponse();

        $this->components->twoColumnDetail('Account ID', (string) ($account['accountId'] ?? '-'));
        $this->components->twoColumnDetail('Account UID', (string) ($account['accountUid'] ?? '-'));

        foreach ($channels as $channel) {
            $this->components->twoColumnDetail(
                sprintf('Channel #%s (%s)', $channel['channelNo'] ?? '?', $channel['channelType'] ?? '?'),
                (string) ($channel['name'] ?? '-'),
            );
        }

        if ($response !== null) {
            $this->components->twoColumnDetail('Trace ID', $response->header('GNCP-GW-Trace-ID') ?: '-');
            $this->components->twoColumnDetail('Rate limit remaining', $response->header('GNCP-GW-RateLimit-Remaining') ?: '-');
            $this->components->twoColumnDetail('Quota remaining', $response->header('GNCP-GW-Quota-Remaining') ?: '-');
        }

        $this->components->info('Naver Commerce API is reachable.');

        return self::SUCCESS;
    }
}
