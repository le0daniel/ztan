<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Scalars;

use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Scalars\FloatType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FloatTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, float}>
     */
    public static function validFloatProvider(): iterable
    {
        yield 'zero' => [0.0, 0.0];
        yield 'positive float' => [3.14, 3.14];
        yield 'negative float' => [-2.5, -2.5];
        yield 'integer' => [42, 42.0];
    }

    #[DataProvider('validFloatProvider')]
    public function testExecuteAcceptsValidFloats(mixed $input, float $expected): void
    {
        $type = new FloatType();
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame($expected, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'string' => ['hello'];
        yield 'numeric string' => ['3.14'];
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsNonFloats(mixed $input): void
    {
        $type = new FloatType();
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected float.', $context->issues[0]->message);
    }

    /**
     * @return iterable<string, array{mixed, float}>
     */
    public static function coerceProvider(): iterable
    {
        yield 'float unchanged' => [3.14, 3.14];
        yield 'int coerced' => [42, 42.0];
        yield 'int zero coerced' => [0, 0.0];
        yield 'true coerced' => [true, 1.0];
        yield 'false coerced' => [false, 0.0];
        yield 'numeric string' => ['3.14', 3.14];
        yield 'int string' => ['42', 42.0];
        yield 'negative numeric string' => ['-2.5', -2.5];
    }

    #[DataProvider('coerceProvider')]
    public function testExecuteWithCoerce(mixed $input, float $expected): void
    {
        $type = new FloatType(coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame($expected, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function coerceStillRejectsProvider(): iterable
    {
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'object' => [new \stdClass()];
        yield 'non-numeric string' => ['hello'];
        yield 'empty string' => [''];
    }

    #[DataProvider('coerceStillRejectsProvider')]
    public function testExecuteWithCoerceStillRejectsNonCoercible(mixed $input): void
    {
        $type = new FloatType(coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected float.', $context->issues[0]->message);
    }
}
