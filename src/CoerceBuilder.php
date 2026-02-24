<?php declare(strict_types=1);

namespace Le0daniel\Assertions;

use Le0daniel\Assertions\Types\Scalars\BoolType;
use Le0daniel\Assertions\Types\Scalars\EnumType;
use Le0daniel\Assertions\Types\Scalars\FloatType;
use Le0daniel\Assertions\Types\Scalars\IntType;
use Le0daniel\Assertions\Types\Scalars\LiteralType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use UnitEnum;

final readonly class CoerceBuilder
{
    public function string(): StringType
    {
        return new StringType(coerce: true);
    }

    public function int(): IntType
    {
        return new IntType(coerce: true);
    }

    public function float(): FloatType
    {
        return new FloatType(coerce: true);
    }

    public function bool(): BoolType
    {
        return new BoolType(coerce: true);
    }

    /**
     * @template T of string|int|float|bool|UnitEnum
     * @param T $literal
     * @return LiteralType<T>
     */
    public function literal(string|int|float|bool|UnitEnum $literal): LiteralType
    {
        return new LiteralType($literal, coerce: true);
    }

    /**
     * @template T of UnitEnum
     * @param class-string<T> $enumClass
     * @return EnumType<T>
     */
    public function enum(string $enumClass): EnumType
    {
        return new EnumType($enumClass, coerce: true);
    }
}
