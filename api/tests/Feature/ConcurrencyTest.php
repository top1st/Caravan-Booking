<?php

use Symfony\Component\Process\Process;

/**
 * Starts one OS process per body against a shared file database, all released at the same
 * instant, and returns their HTTP statuses plus the rows that were actually committed.
 */
function raceBookings(array $bodies): array
{
    $database = tempnam(sys_get_temp_dir(), 'bookings_race_');
    $env = ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'APP_ENV' => 'testing'];

    try {
        $migrate = new Process([PHP_BINARY, 'artisan', 'migrate', '--force'], base_path(), $env);
        $migrate->mustRun();

        $startAt = microtime(true) + 3;
        $workers = array_map(function (array $body) use ($startAt, $env) {
            $worker = new Process(
                [PHP_BINARY, base_path('tests/concurrency_worker.php'), (string) $startAt, json_encode($body)],
                base_path(),
                $env,
                null,
                60,
            );
            $worker->start();

            return $worker;
        }, $bodies);

        $statuses = [];
        foreach ($workers as $worker) {
            $worker->wait();
            $statuses[] = trim($worker->getOutput()).trim($worker->getErrorOutput());
        }

        $pdo = new PDO("sqlite:$database");
        $rows = $pdo->query('select starts_at from bookings')->fetchAll(PDO::FETCH_COLUMN);
        $pdo = null;

        return [$statuses, $rows];
    } finally {
        @unlink($database);
    }
}

function raceBody(string $start, string $end): array
{
    return [
        'customer_name' => 'Racer',
        'customer_email' => 'racer@example.com',
        'rego' => 'RACE01',
        'service_bay' => 'Bay 1',
        'starts_at' => $start,
        'ends_at' => $end,
        'status' => 'confirmed',
    ];
}

it('lets exactly one of many simultaneous overlapping requests win', function () {
    $bodies = array_map(
        fn (int $minute) => raceBody(sprintf('2026-12-01T09:%02d:00Z', $minute), '2026-12-01T10:00:00Z'),
        range(0, 7),
    );

    [$statuses, $rows] = raceBookings($bodies);

    sort($statuses);
    expect($statuses)->toBe(['201', ...array_fill(0, 7, '422')]);
    expect($rows)->toHaveCount(1);
});

it('does not falsely reject simultaneous adjacent requests', function () {
    $bodies = array_map(
        fn (int $hour) => raceBody(sprintf('2026-12-01T%02d:00:00Z', $hour), sprintf('2026-12-01T%02d:00:00Z', $hour + 1)),
        range(8, 15),
    );

    [$statuses, $rows] = raceBookings($bodies);

    expect($statuses)->toBe(array_fill(0, 8, '201'));
    expect($rows)->toHaveCount(8);
});
