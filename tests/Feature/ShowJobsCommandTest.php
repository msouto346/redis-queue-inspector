<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use function Pest\Laravel\artisan;

beforeEach(function () {
    Redis::flushall();
});

it('shows message when no jobs exist', function () {
    artisan('queue:inspect')
        ->assertSuccessful()
        ->expectsOutput('No matching delayed jobs found.');
});

it('filters by --queue', function () {
    $payload = json_encode([
        'uuid' => 'uuid-queue-1',
        'displayName' => 'App\\Jobs\\QueuedJob',
        'data' => ['command' => serialize('dummy')],
    ]);

    Redis::zadd('queues:other:delayed', time() + 300, $payload);

    artisan('queue:inspect --queue=default --count --json')
        ->assertSuccessful()
        ->expectsOutput(json_encode(['count' => 0], JSON_PRETTY_PRINT));

    artisan('queue:inspect --queue=other --count --json')
        ->assertSuccessful()
        ->expectsOutput(json_encode(['count' => 1], JSON_PRETTY_PRINT));
});

it('filters by --job (partial match)', function () {
    $payload = json_encode([
        'uuid' => 'uuid-job-1',
        'displayName' => 'App\\Jobs\\TestNotificationJob',
        'data' => ['command' => serialize('dummy')],
    ]);

    Redis::zadd('queues:default:delayed', time() + 300, $payload);

    artisan('queue:inspect --job=Notification --count --json')
        ->assertSuccessful()
        ->expectsOutput(json_encode(['count' => 1], JSON_PRETTY_PRINT));
});

it('filters by --uuid (exact match)', function () {
    $uuid = 'exact-uuid-1234';
    $payload = json_encode([
        'uuid' => $uuid,
        'displayName' => 'App\\Jobs\\Something',
        'data' => ['command' => serialize('dummy')],
    ]);

    Redis::zadd('queues:default:delayed', time() + 300, $payload);

    artisan("queue:inspect --uuid={$uuid} --count --json")
        ->assertSuccessful()
        ->expectsOutput(json_encode(['count' => 1], JSON_PRETTY_PRINT));
});

it('filters by --identifier (serialized in payload)', function () {
    $identifier = 42;
    $serialized = serialize((object) ['id' => $identifier]);

    $payload = json_encode([
        'uuid' => 'uuid-identifier',
        'displayName' => 'App\\Jobs\\TargetJob',
        'data' => ['command' => $serialized],
    ]);

    Redis::zadd('queues:default:delayed', time() + 300, $payload);

    artisan("queue:inspect --identifier={$identifier} --count --json")
        ->assertSuccessful()
        ->expectsOutput(json_encode(['count' => 1], JSON_PRETTY_PRINT));
});

it('filters jobs by --from and --to date range', function () {
    $today = now();
    $releaseAt = $today->copy()->addHours(2); // falls within today

    Redis::zadd('queues:default:delayed', $releaseAt->timestamp, json_encode([
        'uuid' => 'uuid-date-range',
        'displayName' => 'App\\Jobs\\ScheduledJob',
        'data' => ['command' => serialize('dummy')],
    ]));

    Artisan::call('queue:inspect --from=' . $today->format('Y-m-d') . ' --to=' . $today->format('Y-m-d') . ' --json');
    $output = Artisan::output();
    $json = json_decode($output, true);

    expect($json)
        ->toHaveKey('jobs')
        ->and($json['jobs'][0]['job'])->toBe('App\\Jobs\\ScheduledJob');
});

it('respects --limit and --page', function () {
    Redis::flushall();

    foreach (range(1, 5) as $i) {
        Redis::zadd('queues:default:delayed', time() + $i, json_encode([
            'uuid' => "uuid-{$i}",
            'displayName' => "App\\Jobs\\PaginatedJob{$i}",
            'data' => ['command' => serialize('dummy')],
        ]));
    }

    // Page 1
    Artisan::call('queue:inspect --limit=2 --page=1 --json');
    $outputPage1 = Artisan::output();
    $jsonPage1 = json_decode($outputPage1, true);

    expect($jsonPage1)->toHaveKey('jobs');
    expect($jsonPage1['jobs'])->toHaveCount(2);
    expect($jsonPage1['jobs'][0]['job'])->toBe('App\\Jobs\\PaginatedJob1');
    expect($jsonPage1['jobs'][1]['job'])->toBe('App\\Jobs\\PaginatedJob2');

    // Page 2
    Artisan::call('queue:inspect --limit=2 --page=2 --json');
    $outputPage2 = Artisan::output();
    $jsonPage2 = json_decode($outputPage2, true);

    expect($jsonPage2)->toHaveKey('jobs');
    expect($jsonPage2['jobs'])->toHaveCount(2);
    expect($jsonPage2['jobs'][0]['job'])->toBe('App\\Jobs\\PaginatedJob3');
    expect($jsonPage2['jobs'][1]['job'])->toBe('App\\Jobs\\PaginatedJob4');
});

it('outputs in JSON when --json is used', function () {
    Redis::flushall();

    Redis::zadd('queues:default:delayed', time() + 300, json_encode([
        'uuid' => 'uuid-json-output',
        'displayName' => 'App\\Jobs\\JsonJob',
        'data' => ['command' => serialize('dummy')],
    ]));

    Artisan::call('queue:inspect --json');
    $output = Artisan::output();

    $data = json_decode($output, true);

    expect($data)->toBeArray()
        ->and($data)->toHaveKey('jobs')
        ->and($data['jobs'])->toBeArray()
        ->and($data['jobs'][0]['job'])->toBe('App\\Jobs\\JsonJob');
});

it('shows total count when using --count', function () {
    Redis::zadd('queues:default:delayed', time() + 300, json_encode([
        'uuid' => 'uuid-count',
        'displayName' => 'App\\Jobs\\CountingJob',
        'data' => ['command' => serialize('dummy')],
    ]));

    artisan('queue:inspect --count --json')
        ->assertSuccessful()
        ->expectsOutput(json_encode(['count' => 1], JSON_PRETTY_PRINT));
});
