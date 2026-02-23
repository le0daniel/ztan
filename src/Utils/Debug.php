<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Utils;

use UnitEnum;

final class Debug
{
    public static function getType(mixed $value): string
    {
        return match (true) {
            is_object($value) => self::getObjectType($value),
            is_bool($value) => 'bool<' . ($value ? 'true' : 'false') . '>',
            is_int($value) => "int<{$value}>",
            is_float($value) => "float<{$value}>",
            is_string($value) => "string<'" . self::truncate($value) . "'>",
            is_array($value) => 'array',
            is_resource($value) => 'resource',
            is_null($value) => 'NULL',
            default => "unknown",
        };
    }

    private static function truncate(string $string, int $maxLength = 50): string
    {
        return mb_strlen($string) > $maxLength ? mb_substr($string, 0, $maxLength - 3) . '...' : $string;
    }

    private static function getObjectType(object $value): string
    {
        $className = get_class($value);

        if ($value instanceof UnitEnum) {
            return "enum<{$className}::{$value->name}>";
        }

        return "object<{$className}>";
    }
}