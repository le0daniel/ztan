<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

enum Value
{
    case INVALID;

    /**
     * @phpstan-assert-if-true Value::INVALID $value
     * @param mixed $value
     * @return bool
     */
    public static function isInvalid(mixed $value): bool
    {
        return $value === self::INVALID;
    }
}