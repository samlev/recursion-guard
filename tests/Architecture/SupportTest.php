<?php

arch('support does not contain classes')
    ->expect('RecursionGuard\Support')
    ->not->toBeClasses();
