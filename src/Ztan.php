<?php declare(strict_types=1);

namespace Le0daniel\Ztan;

use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\DiscriminatedUnionType;
use Le0daniel\Ztan\Types\Complex\ListType;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\Complex\TupleType;
use Le0daniel\Ztan\Types\Complex\UnionType;
use Le0daniel\Ztan\Types\Scalars\BoolType;
use Le0daniel\Ztan\Types\Scalars\DateTimeStringType;
use Le0daniel\Ztan\Types\Scalars\EnumType;
use Le0daniel\Ztan\Types\Scalars\FloatType;
use Le0daniel\Ztan\Types\Scalars\InstanceType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\LiteralType;
use Le0daniel\Ztan\Types\Scalars\MixedType;
use Le0daniel\Ztan\Types\Scalars\NeverType;
use Le0daniel\Ztan\Types\Scalars\NullType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use Le0daniel\Ztan\Contracts\Shape;
use UnitEnum;

final class Ztan
{
    private function __construct() {}

    public static function string(): StringType
    {
        return new StringType();
    }

    public static function int(): IntType
    {
        return new IntType();
    }

    public static function float(): FloatType
    {
        return new FloatType();
    }

    public static function bool(): BoolType
    {
        return new BoolType();
    }

    public static function mixed(): MixedType
    {
        return new MixedType();
    }

    public static function never(): NeverType
    {
        return new NeverType();
    }

    public static function dateTimeString(string $format): DateTimeStringType
    {
        return new DateTimeStringType($format);
    }

    /**
     * @template T of string|int|float|bool|UnitEnum
     * @param T $literal
     * @return LiteralType<T>
     */
    public static function literal(string|int|float|bool|UnitEnum $literal): LiteralType
    {
        return new LiteralType($literal);
    }

    public static function null(): NullType
    {
        return new NullType();
    }

    /**
     * @template T of UnitEnum
     * @param class-string<T> $enumClass
     * @return EnumType<T>
     */
    public static function enum(string $enumClass): EnumType
    {
        return new EnumType($enumClass);
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return InstanceType<T>
     */
    public static function instance(string $className): InstanceType
    {
        return new InstanceType($className);
    }

    /**
     * @template TValue
     * @param Type<TValue> $type
     * @return ListType<TValue>
     */
    public static function list(Type $type): ListType
    {
        return new ListType($type);
    }

    /**
     * @template TValue
     * @param Type<TValue> $type
     * @return RecordType<TValue>
     */
    public static function record(Type $type): RecordType
    {
        return new RecordType($type);
    }

    /**
     * @param array<string, Type<mixed>> $properties
     * @phpstan-ignore missingType.generics (resolved by ZtanStaticMethodReturnTypeExtension)
     */
    public static function arrayShape(array $properties): ArrayShapeType
    {
        return new ArrayShapeType($properties);
    }

    /**
     * @param array<string, Type<mixed>> $properties
     * @phpstan-ignore missingType.generics (resolved by ZtanStaticMethodReturnTypeExtension)
     */
    public static function objectShape(array $properties): ObjectShapeType
    {
        return new ObjectShapeType($properties);
    }

    /**
     * @param Type<mixed> ...$types
     * @phpstan-ignore missingType.generics (resolved by ZtanStaticMethodReturnTypeExtension)
     */
    public static function union(Type ...$types): UnionType
    {
        return new UnionType(...$types);
    }

    /**
     * @param Type<mixed> ...$types
     * @phpstan-ignore missingType.generics (resolved by ZtanStaticMethodReturnTypeExtension)
     */
    public static function tuple(Type ...$types): TupleType
    {
        return new TupleType(...$types);
    }

    /**
     * @param string $discriminator
     * @param list<Shape&Type<mixed>> $shapes
     * @phpstan-ignore missingType.generics (resolved by ZtanStaticMethodReturnTypeExtension)
     */
    public static function discriminatedUnion(string $discriminator, array $shapes): DiscriminatedUnionType
    {
        return new DiscriminatedUnionType($discriminator, $shapes);
    }

    public static function coerce(): CoerceBuilder
    {
        return new CoerceBuilder();
    }
}
