<?php

declare(strict_types=1);

arch('support does not contain classes')
    ->expect('RecursionGuard\Support')
    ->not->toBeClasses();
