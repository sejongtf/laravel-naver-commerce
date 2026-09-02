<?php

namespace Sejongtf\LaravelNaverCommerce\Console;

use Illuminate\Console\Command;
use Sejongtf\LaravelNaverCommerce\Auth\TokenManager;
use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;

/**
 * Issue (or read from cache) an access token and print its details.
 */
class TokenCommand extends Command
{
    protected $signature = 'naver-commerce:token
        {--seller= : Seller account ID; issues a SELLER token instead of SELF}
        {--fresh : Discard the cached token and issue a new one}
        {--show : Print the full access token instead of a masked one}';

    protected $description = 'Issue or inspect a Naver Commerce API access token';

    public function handle(TokenManager $tokens): int
    {
        $accountId = $this->option('seller') ?: null;
        $type = $accountId === null ? TokenManager::TYPE_SELF : TokenManager::TYPE_SELLER;

        if ($this->option('fresh')) {
            $tokens->forget($type, $accountId);
        }

        try {
            $token = $tokens->token($type, $accountId);
        } catch (NaverCommerceException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Type', $type);
        $this->components->twoColumnDetail('Account ID', $accountId ?? '-');
        $this->components->twoColumnDetail('Cache key', $tokens->cacheKey($type, $accountId));
        $this->components->twoColumnDetail('Access token', $this->option('show') ? $token : $this->mask($token));

        if (! $this->option('show')) {
            $this->components->info('Use --show to print the full token.');
        }

        return self::SUCCESS;
    }

    protected function mask(string $token): string
    {
        if (strlen($token) <= 12) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 6).str_repeat('*', 8).substr($token, -6);
    }
}
