<?php

declare(strict_types=1);

namespace Msouto\RedisQueueInspector\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Msouto\RedisQueueInspector\Dto\JobQuery;
use Msouto\RedisQueueInspector\Services\Scanner;

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
        {--uuid= : Filter by exact job UUID}';

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
            after: $from,
            before: $to,
            limit: $limit,
            page: $page,
            identifier: $identifier,
            uuid: $uuid,
        );

        $result = $scanner->getDelayedJobs($query);

        $jobs = $result['jobs'];
        $total = $result['total'];

        if ($this->option('count')) {
            $this->info("{$total} matching delayed jobs.");
            return;
        }

        if (empty($jobs)) {
            $this->warn('No matching delayed jobs found.');
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
