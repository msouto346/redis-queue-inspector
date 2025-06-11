<?php

declare(strict_types=1);

namespace Pdmfc\RedisQueueInspector\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Pdmfc\RedisQueueInspector\Dto\JobQuery;
use Pdmfc\RedisQueueInspector\Services\Scanner;

final class ShowJobsCommand extends Command
{
    protected $signature = 'queue:inspect
        {--queue=default : Comma-separated list of queues to inspect}
        {--job= : Filter by partial job class name}
        {--from= : Jobs scheduled from this date (Y-m-d)}
        {--to= : Jobs scheduled up to this date (Y-m-d)}
        {--limit=50 : Number of jobs per page}
        {--page=1 : Page number for pagination}
        {--count : Only return a total count of matching jobs}
        {--identifier= : Return jobs that reference this model ID anywhere in the payload}
        {--uuid= : Filter by exact job UUID}
        {--json : Output results in JSON format}';

    protected $description = 'Inspect delayed Redis queue jobs with filters';

    public function handle(): void
    {
        $queues = explode(',', $this->option('queue'));
        $jobFilter = $this->option('job');
        $from = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : null;
        $limit = (int) $this->option('limit');
        $page = (int) $this->option('page');
        $identifier = $this->option('identifier') ? (int) $this->option('identifier') : null;
        $uuid = $this->option('uuid') ?: null;

        $scanner = new Scanner();

        $query = new JobQuery(
            queues: $queues,
            jobFilter: $jobFilter,
            from: $from,
            to: $to,
            limit: $limit,
            page: $page,
            identifier: $identifier,
            uuid: $uuid,
        );

        $result = $scanner->getDelayedJobs($query);

        $jobs = $result['jobs'];
        $total = $result['total'];

        if ($this->option('count')) {
            if ($this->option('json')) {
                $this->line(json_encode(['count' => $total], JSON_PRETTY_PRINT));
            } else {
                $this->info("{$total} matching delayed jobs.");
            }
            return;
        }

        if (empty($jobs)) {
            if ($this->option('json')) {
                $this->line(json_encode(['jobs' => []], JSON_PRETTY_PRINT));
            } else {
                $this->warn('No matching delayed jobs found.');
            }
            return;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'jobs' => $jobs,
            ], JSON_PRETTY_PRINT));
            return;
        }

        $this->info("Showing {$limit} jobs (page {$page}) of {$total} matching jobs.");

        if ($total > $limit * $page) {
            $this->line("Tip: Run with --page=" . ($page + 1) . " to see more.");
        }

        $this->table(
            ['Queue', 'Job Name', 'Job ID', 'Release At'],
            $jobs,
        );
    }
}
