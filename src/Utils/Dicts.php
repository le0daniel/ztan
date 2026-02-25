<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Utils;

use Closure;

final readonly class Dicts
{
    /**
     * @template TKey
     * @template TValue
     * @template TReturnValue
     * @param array<TKey, TValue> $array
     * @param Closure(TKey, TValue): TReturnValue  $closure
     * @return array<TKey, TReturnValue>
     */
    public static function mapWithKeys(array $array, Closure $closure): array
    {
        $mapped = [];
        foreach ($array as $key => $value) {
            $mapped[$key] = $closure($key, $value);
        }
        return $mapped;
    }
}