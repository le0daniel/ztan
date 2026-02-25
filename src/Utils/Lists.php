<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Utils;

final readonly class Lists
{
    /**
     * @template T
     * @param list<null|T> $values
     * @return list<T>
     */
    public static function filterNullValues(array $values): array
    {
        return array_filter($values, fn($value) => $value !== null) |> array_values(...);
    }
}