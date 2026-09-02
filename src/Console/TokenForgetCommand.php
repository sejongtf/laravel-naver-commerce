<?php

namespace Sejongtf\LaravelNaverCommerce\Console;

use Illuminate\Console\Command;
use Sejongtf\LaravelNaverCommerce\Auth\TokenManager;

/**
 * Remove a cached access token so the next request issues a new one.
 */
class TokenForgetCommand extends Command
{
    protected $signature = 'naver-commerce:token:forget
        {--seller= : Seller account ID; forgets the SELLER token instead of SELF}';

    protected $description = 'Forget the cached Naver Commerce API access token';

    public function handle(TokenManager $tokens): int
    {
        $accountId = $this->option('seller') ?: null;
        $type = $accountId === null ? TokenManager::TYPE_SELF : TokenManager::TYPE_SELLER;

        $tokens->forget($type, $accountId);

        $this->components->info(sprintf('Forgot cached token [%s].', $tokens->cacheKey($type, $accountId)));

        return self::SUCCESS;
    }
}
