<?php

declare(strict_types=1);

namespace App\Domain\Results\ValueObject;

use App\Domain\Shared\Exception\InvalidValueException;

final readonly class FinishTime
{
    public function __construct(private int $seconds)
    {
        if ($seconds < 0) {
            throw new InvalidValueException('Finish time cannot be negative');
        }
    }

    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds);
    }

    public static function fromString(string $time): self
    {
        $parts = explode(':', $time);
        if (count($parts) !== 3) {
            throw new InvalidValueException('Time must be in HH:MM:SS format');
        }

        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];
        $secs = (int) $parts[2];

        if ($minutes > 59 || $secs > 59) {
            throw new InvalidValueException('Invalid time format');
        }

        return new self($hours * 3600 + $minutes * 60 + $secs);
    }

    public function seconds(): int
    {
        return $this->seconds;
    }

    public function format(): string
    {
        $hours = (int) floor($this->seconds / 3600);
        $minutes = (int) floor(($this->seconds % 3600) / 60);
        $secs = $this->seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
