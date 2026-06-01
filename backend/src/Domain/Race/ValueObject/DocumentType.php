<?php

declare(strict_types=1);

namespace App\Domain\Race\ValueObject;

use App\Domain\Shared\Exception\InvalidValueException;

final readonly class DocumentType
{
    public const ROUTE = 'route';
    public const PROFILE = 'profile';
    public const RESULTS = 'results';
    public const GENERAL = 'general';
    public const OTHER = 'other';

    public const VALID_TYPES = [
        self::ROUTE,
        self::PROFILE,
        self::RESULTS,
        self::GENERAL,
        self::OTHER,
    ];

    public function __construct(
        private string $value,
    ) {
        if (!\in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidValueException(sprintf(
                'Invalid document type "%s". Allowed: %s',
                $value,
                implode(', ', self::VALID_TYPES)
            ));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
