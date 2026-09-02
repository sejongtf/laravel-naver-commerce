<?php

namespace Sejongtf\LaravelNaverCommerce\Console;

use Illuminate\Console\Command;
use Sejongtf\LaravelNaverCommerce\Console\Concerns\InteractsWithApi;
use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;
use Sejongtf\LaravelNaverCommerce\NaverCommerce;

/**
 * Dump the category tree to a JSON file (or stdout) for local caching or seeding.
 */
class CategoriesExportCommand extends Command
{
    use InteractsWithApi;

    protected $signature = 'naver-commerce:categories:export
        {path? : Output file; prints to stdout when omitted}
        {--last : Export leaf categories only}
        {--seller= : Seller account ID; calls the API with a SELLER token}';

    protected $description = 'Export Naver Commerce categories as JSON';

    public function handle(NaverCommerce $api): int
    {
        try {
            $categories = $this->resolveApi($api)->categories()->all($this->option('last') ? true : null);
        } catch (NaverCommerceException $e) {
            $this->reportException($e);

            return self::FAILURE;
        }

        $json = $this->toJson($categories);
        $path = $this->argument('path');

        if ($path === null) {
            $this->line($json);

            return self::SUCCESS;
        }

        $dir = dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            $this->components->error("Cannot create directory [{$dir}].");

            return self::FAILURE;
        }

        if (file_put_contents($path, $json.PHP_EOL) === false) {
            $this->components->error("Cannot write to [{$path}].");

            return self::FAILURE;
        }

        $this->components->info(sprintf('Exported %d categories to %s.', count($categories), $path));

        return self::SUCCESS;
    }
}
