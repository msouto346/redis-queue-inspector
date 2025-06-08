<?php

declare(strict_types=1);

namespace Msouto\RedisQueueInspector\Dto;

use Carbon\Carbon;

final class JobQuery
{
    /**
     * @param string[] $queues
     * @param ?string $jobFilter
     * @param ?Carbon $from
     * @param ?Carbon $to
     * @param int $limit
     * @param int $page
     * @param ?int $identifier
     * @param ?string $uuid
     */
    public function __construct(
        public array $queues,
        public ?string $jobFilter = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
        public int $limit = 50,
        public int $page = 1,
        public ?int $identifier = null,
        public ?string $uuid = null,
    ) {}
}
