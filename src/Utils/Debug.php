<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Utils;

final class Debug
{
    public static function getType(mixed $value): string
    {
        $type = gettype($value);

        if (is_object($value)) {
            return self::getObjectType($value);
        }

        return match ($type) {
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'string' => 'string',
            'array' => 'array',
            'resource' => 'resource',
            'NULL' => 'NULL',
            default => "unknown",
        };
    }

    private static function getObjectType(object $value): string
    {
        return get_class($value);
    }
}