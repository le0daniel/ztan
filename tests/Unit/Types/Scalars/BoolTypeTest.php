<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Scalars;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Scalars\BoolType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BoolTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function validBoolProvider(): iterable
    {
        yield 'true' => [true, true];
        yield 'false' => [false, false];
    }

    #[DataProvider('validBoolProvider')]
    public function testExecuteAcceptsValidBools(mixed $input, bool $expected): void
    {
        $type = new BoolType();
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
        yield 'zero' => [0];
        yield 'one' => [1];
        yield 'float' => [3.14];
        yield 'string' => ['hello'];
        yield 'string true' => ['true'];
        yield 'string false' => ['false'];
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'object' => [new \stdClass()];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsNonBools(mixed $input): void
    {
        $type = new BoolType();
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected boolean.', $context->issues[0]->message);
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function coerceProvider(): iterable
    {
        yield 'true unchanged' => [true, true];
        yield 'false unchanged' => [false, false];
        yield 'int 1' => [1, true];
        yield 'int 0' => [0, false];
        yield 'float 1.0' => [1.0, true];
        yield 'float 0.0' => [0.0, false];
        yield 'string true' => ['true', true];
        yield 'string false' => ['false', false];
    }

    #[DataProvider('coerceProvider')]
    public function testExecuteWithCoerce(mixed $input, bool $expected): void
    {
        $type = new BoolType(coerce: true);
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
        yield 'int 2' => [2];
        yield 'int -1' => [-1];
        yield 'float 0.5' => [0.5];
        yield 'string yes' => ['yes'];
        yield 'string no' => ['no'];
        yield 'string 1' => ['1'];
        yield 'string 0' => ['0'];
        yield 'empty string' => [''];
    }

    #[DataProvider('coerceStillRejectsProvider')]
    public function testExecuteWithCoerceStillRejectsNonCoercible(mixed $input): void
    {
        $type = new BoolType(coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected boolean.', $context->issues[0]->message);
    }
}
