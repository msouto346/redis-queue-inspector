<?php

declare(strict_types=1);

namespace Msouto\RedisQueueInspector\Dto;

use Carbon\Carbon;

final class JobQuery
{
    public function __construct(
        public array $queues,
        public ?string $jobFilter = null,
        public ?Carbon $after = null,
        public ?Carbon $before = null,
        public int $limit = 50,
        public int $page = 1,
        public ?int $identifier = null,
        public ?string $uuid = null,
    ) {}
}
