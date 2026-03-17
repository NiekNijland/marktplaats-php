<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Support;

interface ClockInterface
{
    public function sleepMilliseconds(int $milliseconds): void;
}
