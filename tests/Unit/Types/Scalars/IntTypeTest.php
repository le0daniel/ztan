<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Scalars;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Scalars\IntType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IntTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function validIntProvider(): iterable
    {
        yield 'zero' => [0, 0];
        yield 'positive int' => [42, 42];
        yield 'negative int' => [-7, -7];
        yield 'max int' => [PHP_INT_MAX, PHP_INT_MAX];
    }

    #[DataProvider('validIntProvider')]
    public function testExecuteAcceptsValidInts(mixed $input, int $expected): void
    {
        $type = new IntType();
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
        yield 'float' => [3.14];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'string' => ['hello'];
        yield 'numeric string' => ['42'];
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsNonInts(mixed $input): void
    {
        $type = new IntType();
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected integer.', $context->issues[0]->message);
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function coerceProvider(): iterable
    {
        yield 'int unchanged' => [42, 42];
        yield 'float coerced' => [3.14, 3];
        yield 'float zero' => [0.0, 0];
        yield 'true coerced' => [true, 1];
        yield 'false coerced' => [false, 0];
        yield 'numeric string' => ['42', 42];
        yield 'negative numeric string' => ['-7', -7];
        yield 'float string' => ['3.14', 3];
    }

    #[DataProvider('coerceProvider')]
    public function testExecuteWithCoerce(mixed $input, int $expected): void
    {
        $type = new IntType(coerce: true);
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
        $type = new IntType(coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected integer.', $context->issues[0]->message);
    }
}
