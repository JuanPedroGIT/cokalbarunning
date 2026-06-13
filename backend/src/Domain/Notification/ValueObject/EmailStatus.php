<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

final readonly class EmailStatus
{
    public const PENDING = 'pending';
    public const SENT = 'sent';
    public const ERROR = 'error';

    public function __construct(private string $value)
    {
        if (!\in_array($value, [self::PENDING, self::SENT, self::ERROR], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid email status: %s', $value));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isSent(): bool
    {
        return $this->value === self::SENT;
    }

    public function isError(): bool
    {
        return $this->value === self::ERROR;
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function sent(): self
    {
        return new self(self::SENT);
    }

    public static function error(): self
    {
        return new self(self::ERROR);
    }
}
