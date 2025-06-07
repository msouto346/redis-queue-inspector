<?php

declare(strict_types=1);

use Msouto\RedisQueueInspector\Example;

it('foo', function (): void {
    $example = new Example();

    $result = $example->foo();

    expect($result)->toBe('bar');
});
