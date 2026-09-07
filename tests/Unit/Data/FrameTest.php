<?php

declare(strict_types=1);

use RecursionGuard\Data\Frame;

covers(Frame::class);

it('has default constructor properties frame that is empty', function () {
    $frame = new Frame();

    expect($frame->file)->toBe('')
        ->and($frame['file'])->toBe('')
        ->and($frame->class)->toBe('')
        ->and($frame['class'])->toBe('')
        ->and($frame->function)->toBe('')
        ->and($frame['function'])->toBe('')
        ->and($frame->line)->toBe(0)
        ->and($frame['line'])->toBe(0)
        ->and($frame->object)->toBeNull()
        ->and($frame['object'])->toBeNull();
});
