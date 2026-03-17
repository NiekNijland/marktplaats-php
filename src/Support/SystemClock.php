<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Support;

class SystemClock implements ClockInterface
{
    public function sleepMilliseconds(int $milliseconds): void
    {
        if ($milliseconds <= 0) {
            return;
        }

        usleep($milliseconds * 1000);
    }
}
