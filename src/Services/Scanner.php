<?php

declare(strict_types=1);

namespace Msouto\RedisQueueInspector\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Msouto\RedisQueueInspector\Dto\JobQuery;

final class Scanner
{
    /**
     * @param string[] $queues
     * @return array{jobs: list<array{queue: string, job: string, job_id: string, release_at: string}>, total: int}
     */
    public function getDelayedJobs(JobQuery $query): array
    {
        $results = [];

        foreach ($query->queues as $queue) {
            $key = "queues:{$queue}:delayed";
            $start = 0;
            $end = '+inf';

            $jobs = Redis::zrangebyscore($key, $start, $end, ['withscores' => true]);

            foreach ($jobs as $payload => $timestamp) {
                $releaseTime = Carbon::createFromTimestamp($timestamp);

                if ($query->after && $releaseTime->lt($query->after)) {
                    continue;
                }

                if ($query->before && $releaseTime->gt($query->before)) {
                    continue;
                }

                $data = json_decode($payload, true);
                if ( ! is_array($data)) {
                    continue;
                }

                $jobName = data_get($data, 'displayName');

                if ($query->uuid && ($data['uuid'] ?? null) !== $query->uuid) {
                    continue;
                }

                if ($query->jobFilter && ! str_contains($jobName, $query->jobFilter)) {
                    continue;
                }

                if (null !== $query->identifier) {
                    $serialized = $data['data']['command'] ?? '';
                    if ( ! preg_match('/i:' . preg_quote((string) $query->identifier, '/') . ';/', $serialized)) {
                        continue;
                    }
                }

                $results[] = [
                    'queue' => $queue,
                    'job' => $jobName,
                    'job_id' => $data['uuid'] ?? $data['id'] ?? 'N/A',
                    'release_at' => $releaseTime->toDateTimeString(),
                ];
            }
        }

        $collection = collect($results)->sortBy('release_at');
        $total = $collection->count();
        $paginated = $collection->forPage($query->page, $query->limit)->values()->all();

        return [
            'jobs' => $paginated,
            'total' => $total,
        ];
    }
}
