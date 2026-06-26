<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

final readonly class EmailType
{
    public const BIB = 'bib';
    public const RAFFLE = 'raffle';
    public const LAST_INSTRUCTIONS = 'last_instructions';
    public const THANKS = 'thanks';
    public const GENERIC = 'generic';

    public function __construct(private string $value)
    {
        if (!\in_array($value, [self::BIB, self::RAFFLE, self::LAST_INSTRUCTIONS, self::THANKS, self::GENERIC], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid email type: %s', $value));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isBib(): bool
    {
        return $this->value === self::BIB;
    }

    public function isRaffle(): bool
    {
        return $this->value === self::RAFFLE;
    }

    public function isLastInstructions(): bool
    {
        return $this->value === self::LAST_INSTRUCTIONS;
    }

    public function isThanks(): bool
    {
        return $this->value === self::THANKS;
    }

    public static function bib(): self
    {
        return new self(self::BIB);
    }

    public static function raffle(): self
    {
        return new self(self::RAFFLE);
    }

    public static function lastInstructions(): self
    {
        return new self(self::LAST_INSTRUCTIONS);
    }

    public static function thanks(): self
    {
        return new self(self::THANKS);
    }

    public function isGeneric(): bool
    {
        return $this->value === self::GENERIC;
    }

    public static function generic(): self
    {
        return new self(self::GENERIC);
    }
}
