<?php

use Illuminate\Support\Facades\Artisan;
use Sejongtf\LaravelNaverCommerce\Tests\Integration\IntegrationTestCase;
use Sejongtf\LaravelNaverCommerce\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(IntegrationTestCase::class)->in('Integration');

/**
 * Run an artisan command and return [exit code, output].
 *
 * `expectsOutputToContain()` matches at most one substring per written line,
 * so command tests inspect the full captured output instead.
 */
function runArtisan(string $command, array $parameters = []): array
{
    $code = Artisan::call($command, $parameters);

    return [$code, Artisan::output()];
}
