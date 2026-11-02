<?php

// Run by ConcurrencyTest as a separate OS process: boots the real app, waits for the
// shared start time, sends one request through the HTTP kernel and prints the status.

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;

[, $startAt, $body] = $argv;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

// Hold the gap between the availability check and the write open. Without this the window is
// too small to hit, and the test passes even with no lock at all (checked by removing the lock).
$app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) {
    if (str_contains($query->sql, 'select exists')) {
        usleep(200_000);
    }
});

time_sleep_until((float) $startAt);

$response = $kernel->handle(Request::create('/api/bookings', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], $body));

echo $response->getStatusCode();
