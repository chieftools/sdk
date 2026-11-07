<?php

namespace ChiefTools\SDK\Webhook\Outbound\Stats;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use ChiefTools\SDK\Webhook\Outbound\Models\Attempt;
use ChiefTools\SDK\Webhook\Outbound\Models\Endpoint;

/**
 * Attempts of an endpoint per time bucket for its graphs, read from the attempts themselves which retention keeps
 * to a bounded number.
 */
class EndpointStats
{
    /** @var array<string, array{hours: int, bucket_hours: int}> */
    public const array RANGES = [
        '24h' => ['hours' => 24, 'bucket_hours' => 1],
        '7d'  => ['hours' => 168, 'bucket_hours' => 6],
        '30d' => ['hours' => 720, 'bucket_hours' => 24],
    ];

    /**
     * Every bucket of the range from old to new, empty buckets included so graphs show the gaps. `timed` counts the
     * attempts with a measured response time, attempts that got no response have none.
     *
     * @return list<array{date: \Carbon\CarbonImmutable, attempts: int, succeeded: int, failed: int, timed: int, duration_sum: int, avg_ms: int|null, max_ms: int|null}>
     */
    public function series(Endpoint $endpoint, string $range = '24h', ?CarbonInterface $now = null): array
    {
        $config      = self::RANGES[$range] ?? self::RANGES['24h'];
        $bucketHours = $config['bucket_hours'];
        $now         = CarbonImmutable::instance($now ?? now())->setTimezone(config('app.timezone'));
        $end         = $now->startOfHour()->addHour();
        $start       = $end->subHours($config['hours']);

        $hours = $this->hourly($endpoint, $start, $end);

        $series = [];

        for ($bucket = $start; $bucket->lt($end); $bucket = $bucket->addHours($bucketHours)) {
            $rows = [];

            for ($hour = 0; $hour < $bucketHours; $hour++) {
                if (isset($hours[$key = $bucket->addHours($hour)->format('Y-m-d H')])) {
                    $rows[] = $hours[$key];
                }
            }

            $attempts  = array_sum(array_column($rows, 'attempts'));
            $timed     = array_sum(array_column($rows, 'timed'));
            $durations = array_sum(array_column($rows, 'duration_sum'));
            $max       = $rows === [] ? null : max(array_column($rows, 'duration_max'));

            $series[] = [
                'date'         => $bucket,
                'attempts'     => $attempts,
                'succeeded'    => $succeeded = array_sum(array_column($rows, 'succeeded')),
                'failed'       => $attempts - $succeeded,
                'timed'        => $timed,
                'duration_sum' => $durations,
                'avg_ms'       => $timed > 0 ? (int)round($durations / $timed) : null,
                'max_ms'       => $max === null ? null : (int)$max,
            ];
        }

        return $series;
    }

    /**
     * Totals over a range, for the summary next to the graphs.
     *
     * @return array{attempts: int, succeeded: int, failed: int, success_rate: float|null, avg_ms: int|null}
     */
    public function summary(Endpoint $endpoint, string $range = '24h', ?CarbonInterface $now = null): array
    {
        $series   = $this->series($endpoint, $range, $now);
        $attempts = array_sum(array_column($series, 'attempts'));
        $succeed  = array_sum(array_column($series, 'succeeded'));
        // Only measured response times count, an attempt without a response has none to average
        $timed     = array_sum(array_column($series, 'timed'));
        $durations = array_sum(array_column($series, 'duration_sum'));

        return [
            'attempts'     => $attempts,
            'succeeded'    => $succeed,
            'failed'       => $attempts - $succeed,
            'success_rate' => $attempts > 0 ? round($succeed / $attempts * 100, 1) : null,
            'avg_ms'       => $timed > 0 ? (int)round($durations / $timed) : null,
        ];
    }

    /**
     * How the attempts of the last day went per endpoint, keyed by endpoint ID, for the success rate and sparkline in
     * endpoint lists: totals and the 24 hours up to now, oldest first. Endpoints without attempts are left out.
     *
     * @param iterable<\ChiefTools\SDK\Webhook\Outbound\Models\Endpoint> $endpoints
     *
     * @return array<string, array{attempts: int, succeeded: int, success_rate: float, hours: list<array{hour: \Carbon\CarbonImmutable, succeeded: int, failed: int}>}>
     */
    public function lastDay(iterable $endpoints, ?CarbonInterface $now = null): array
    {
        $ids = collect($endpoints)->map(static fn (Endpoint $endpoint) => $endpoint->getKey())->all();

        if ($ids === []) {
            return [];
        }

        $hour  = $this->hourExpression();
        $end   = CarbonImmutable::instance($now ?? now())->setTimezone(config('app.timezone'))->startOfHour()->addHour();
        $start = $end->subDay();

        $rows = Attempt::query()
            ->toBase()
            ->whereIn('endpoint_id', $ids)
            ->where('attempted_at', '>=', $start)
            ->where('attempted_at', '<', $end)
            ->selectRaw("endpoint_id, {$hour} as hour, count(*) as attempts, sum(case when succeeded then 1 else 0 end) as succeeded")
            ->groupBy('endpoint_id')
            ->groupByRaw($hour)
            ->get()
            ->groupBy('endpoint_id');

        return $rows->mapWithKeys(static function ($endpointRows, string $endpointId) use ($start): array {
            $byHour    = $endpointRows->keyBy('hour');
            $attempts  = (int)$endpointRows->sum('attempts');
            $succeeded = (int)$endpointRows->sum('succeeded');
            $hours     = [];

            for ($index = 0; $index < 24; $index++) {
                $at  = $start->addHours($index);
                $row = $byHour->get($at->format('Y-m-d H'));

                $hours[] = [
                    'hour'      => $at,
                    'succeeded' => (int)($row->succeeded ?? 0),
                    'failed'    => (int)($row->attempts ?? 0) - (int)($row->succeeded ?? 0),
                ];
            }

            return [$endpointId => [
                'attempts'     => $attempts,
                'succeeded'    => $succeeded,
                'success_rate' => round($succeeded / max(1, $attempts) * 100, 1),
                'hours'        => $hours,
            ]];
        })->all();
    }

    /** Groups `attempted_at` by the hour, as `Y-m-d H`, in the database's dialect. */
    private function hourExpression(): string
    {
        return match (DB::connection((new Attempt)->getConnectionName())->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d %H', attempted_at)",
            'pgsql'  => "to_char(attempted_at, 'YYYY-MM-DD HH24')",
            default  => "date_format(attempted_at, '%Y-%m-%d %H')",
        };
    }

    /** @return array<string, array{attempts: int, succeeded: int, timed: int, duration_sum: int, duration_max: int}> */
    private function hourly(Endpoint $endpoint, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $hour = $this->hourExpression();

        return Attempt::query()
            ->toBase()
            ->where('endpoint_id', $endpoint->getKey())
            ->where('attempted_at', '>=', $start)
            ->where('attempted_at', '<', $end)
            ->selectRaw("{$hour} as hour")
            ->selectRaw('count(*) as attempts')
            ->selectRaw('sum(case when succeeded then 1 else 0 end) as succeeded')
            ->selectRaw('count(duration_ms) as timed')
            ->selectRaw('coalesce(sum(duration_ms), 0) as duration_sum')
            ->selectRaw('coalesce(max(duration_ms), 0) as duration_max')
            ->groupByRaw($hour)
            ->get()
            ->mapWithKeys(static fn (object $row): array => [(string)$row->hour => [
                'attempts'     => (int)$row->attempts,
                'succeeded'    => (int)$row->succeeded,
                'timed'        => (int)$row->timed,
                'duration_sum' => (int)$row->duration_sum,
                'duration_max' => (int)$row->duration_max,
            ]])
            ->all();
    }
}
