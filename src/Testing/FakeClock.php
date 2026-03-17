<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Support\ClockInterface;

class FakeClock implements ClockInterface
{
    /** @var list<int> */
    private array $sleepCalls = [];

    public function sleepMilliseconds(int $milliseconds): void
    {
        $this->sleepCalls[] = $milliseconds;
    }

    /**
     * @return list<int>
     */
    public function getSleepCalls(): array
    {
        return $this->sleepCalls;
    }
}
