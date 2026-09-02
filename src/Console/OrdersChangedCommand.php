<?php

namespace Sejongtf\LaravelNaverCommerce\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Sejongtf\LaravelNaverCommerce\Console\Concerns\InteractsWithApi;
use Sejongtf\LaravelNaverCommerce\Exceptions\NaverCommerceException;
use Sejongtf\LaravelNaverCommerce\NaverCommerce;

/**
 * List product orders whose status changed in a time window (the API's polling primitive).
 *
 * The API accepts at most 24 hours per request, so longer windows are split into
 * 24-hour chunks automatically. `more` pagination inside a chunk is followed only with --all.
 * A truncated result (more rows left, or the request cap hit) exits with code 1 so scripts can detect it.
 */
class OrdersChangedCommand extends Command
{
    use InteractsWithApi;

    /** Safety cap on the total number of requests. */
    protected const MAX_REQUESTS = 200;

    /** Maximum window the API accepts per request. */
    protected const MAX_WINDOW_HOURS = 24;

    /** Time zone assumed for date-times given without an offset (the API works in KST). */
    protected const TIMEZONE = 'Asia/Seoul';

    protected $signature = 'naver-commerce:orders:changed
        {--since=1h : Start of the window; a duration (30m, 6h, 2d) or a date-time}
        {--until= : End of the window; a date-time in KST unless an offset is given (defaults to now)}
        {--type= : lastChangedType filter, e.g. PAYED, DISPATCHED, CLAIM_REQUESTED}
        {--limit= : Max rows per request (API caps at 300)}
        {--all : Follow `more` pagination until each window is exhausted}
        {--json : Print the raw items as JSON instead of a table}
        {--seller= : Seller account ID; calls the API with a SELLER token}';

    protected $description = 'List product orders whose status changed since a given time';

    public function handle(NaverCommerce $api): int
    {
        try {
            $from = $this->parseSince((string) $this->option('since'));
            $until = $this->option('until') ? CarbonImmutable::parse($this->option('until'), self::TIMEZONE) : CarbonImmutable::now();
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::INVALID;
        }

        if ($until->lessThanOrEqualTo($from)) {
            $this->components->error('--until must be later than --since.');

            return self::INVALID;
        }

        $api = $this->resolveApi($api);
        $baseQuery = array_filter([
            'lastChangedType' => $this->option('type'),
            'limitCount' => $this->option('limit') !== null ? (int) $this->option('limit') : null,
        ], fn ($value) => $value !== null);

        $items = [];
        $requests = 0;
        $capReached = false;   // stopped because MAX_REQUESTS was hit (with or without --all)
        $leftUnfollowed = false; // a `more` cursor was returned but --all was not given

        try {
            foreach ($this->windows($from, $until) as [$windowFrom, $windowTo]) {
                $query = $baseQuery + ['lastChangedTo' => $windowTo];
                $cursor = $windowFrom;

                do {
                    if ($requests >= self::MAX_REQUESTS) {
                        $capReached = true;
                        break 2;
                    }

                    $result = $api->orders()->lastChangedStatuses($cursor, $query);
                    $requests++;
                    $items = array_merge($items, $result['data']['lastChangeStatuses'] ?? []);
                    $more = $result['data']['more'] ?? null;

                    if ($more === null) {
                        break;
                    }

                    if (! $this->option('all')) {
                        $leftUnfollowed = true;
                        break;
                    }

                    $cursor = (string) $more['moreFrom'];
                    $query['moreSequence'] = $more['moreSequence'] ?? null;
                } while (true);
            }
        } catch (NaverCommerceException $e) {
            $this->reportException($e);

            return self::FAILURE;
        }

        $warning = match (true) {
            $capReached => 'Stopped after '.self::MAX_REQUESTS.' requests; narrow the window to fetch the rest.',
            $leftUnfollowed => 'More rows available; pass --all to follow pagination.',
            default => null,
        };

        if ($this->option('json')) {
            // Keep stdout valid JSON for piping; the truncation warning goes to stderr.
            $this->line($this->toJson($items));

            if ($warning !== null) {
                $this->output->getErrorStyle()->warning($warning);
            }

            return $warning === null ? self::SUCCESS : self::FAILURE;
        }

        if ($items === []) {
            $this->components->info('No changed product orders in the given window.');
        } else {
            $this->table(
                ['Product order', 'Order', 'Status', 'Changed type', 'Changed at', 'Claim'],
                array_map(fn (array $item) => [
                    $item['productOrderId'] ?? '-',
                    $item['orderId'] ?? '-',
                    $item['productOrderStatus'] ?? '-',
                    $item['lastChangedType'] ?? '-',
                    $item['lastChangedDate'] ?? '-',
                    trim(($item['claimType'] ?? '').' '.($item['claimStatus'] ?? '')) ?: '-',
                ], $items),
            );
        }

        $this->components->twoColumnDetail('Window', $from->setTimezone(self::TIMEZONE)->toIso8601String().' → '.$until->setTimezone(self::TIMEZONE)->toIso8601String());
        $this->components->twoColumnDetail('Requests', (string) $requests);
        $this->components->twoColumnDetail('Rows', (string) count($items));

        if ($warning !== null) {
            $this->components->warn($warning);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Split [from, until] into consecutive windows of at most 24 hours.
     *
     * @return iterable<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    protected function windows(CarbonImmutable $from, CarbonImmutable $until): iterable
    {
        $cursor = $from;

        while ($cursor->lessThan($until)) {
            $end = $cursor->addHours(self::MAX_WINDOW_HOURS)->min($until);

            yield [$cursor, $end];

            $cursor = $end;
        }
    }

    /**
     * Accept a relative duration (`30m`, `6h`, `2d`) or anything Carbon can parse (KST when no offset is given).
     */
    protected function parseSince(string $since): CarbonImmutable
    {
        if (preg_match('/^(\d+)([mhd])$/', $since, $m)) {
            $now = CarbonImmutable::now();

            return match ($m[2]) {
                'm' => $now->subMinutes((int) $m[1]),
                'h' => $now->subHours((int) $m[1]),
                'd' => $now->subDays((int) $m[1]),
            };
        }

        try {
            return CarbonImmutable::parse($since, self::TIMEZONE);
        } catch (\Throwable) {
            throw new InvalidArgumentException("Cannot parse --since value [{$since}]. Use 30m, 6h, 2d or a date-time.");
        }
    }
}
