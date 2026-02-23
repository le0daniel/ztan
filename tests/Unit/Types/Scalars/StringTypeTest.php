<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Scalars;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Scalars\StringType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function validStringProvider(): iterable
    {
        yield 'regular string' => ['hello', 'hello'];
        yield 'empty string' => ['', ''];
        yield 'numeric string' => ['0', '0'];
    }

    #[DataProvider('validStringProvider')]
    public function testExecuteAcceptsValidStrings(mixed $input, string $expected): void
    {
        $type = new StringType();
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
        yield 'integer' => [42];
        yield 'float' => [3.14];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsNonStrings(mixed $input): void
    {
        $type = new StringType();
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected string.', $context->issues[0]->message);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function coerceProvider(): iterable
    {
        yield 'string unchanged' => ['hello', 'hello'];
        yield 'int coerced' => [42, '42'];
        yield 'float coerced' => [3.14, '3.14'];
        yield 'true coerced' => [true, 'true'];
        yield 'false coerced' => [false, 'false'];
    }

    #[DataProvider('coerceProvider')]
    public function testExecuteWithCoerce(mixed $input, string $expected): void
    {
        $type = new StringType(coerce: true);
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
    }

    #[DataProvider('coerceStillRejectsProvider')]
    public function testExecuteWithCoerceStillRejectsNonCoercible(mixed $input): void
    {
        $type = new StringType(coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected string.', $context->issues[0]->message);
    }
}
