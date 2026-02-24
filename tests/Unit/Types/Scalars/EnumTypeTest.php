<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Scalars;

use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Scalars\EnumType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum TestStatus {
    case SUCCESS;
    case FAILURE;
    case PENDING;
}

enum TestBackedStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum TestIntBacked: int {
    case LOW = 1;
    case HIGH = 2;
}

final class EnumTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, TestStatus}>
     */
    public static function validEnumProvider(): iterable
    {
        yield 'success' => [TestStatus::SUCCESS, TestStatus::SUCCESS];
        yield 'failure' => [TestStatus::FAILURE, TestStatus::FAILURE];
        yield 'pending' => [TestStatus::PENDING, TestStatus::PENDING];
    }

    #[DataProvider('validEnumProvider')]
    public function testExecuteAcceptsValid(mixed $input, TestStatus $expected): void
    {
        $type = new EnumType(TestStatus::class);
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
        yield 'string matching case name' => ['SUCCESS'];
        yield 'string lowercase' => ['success'];
        yield 'random string' => ['hello'];
        yield 'empty string' => [''];
        yield 'int' => [42];
        yield 'float' => [3.14];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'object' => [new \stdClass()];
        yield 'wrong enum instance' => [TestBackedStatus::ACTIVE];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsInvalid(mixed $input): void
    {
        $type = new EnumType(TestStatus::class);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Invalid value.', $context->issues[0]->message);
    }

    /**
     * @return iterable<string, array{mixed, TestStatus}>
     */
    public static function coerceProvider(): iterable
    {
        yield 'string SUCCESS' => ['SUCCESS', TestStatus::SUCCESS];
        yield 'string FAILURE' => ['FAILURE', TestStatus::FAILURE];
        yield 'string PENDING' => ['PENDING', TestStatus::PENDING];
        yield 'instance unchanged' => [TestStatus::SUCCESS, TestStatus::SUCCESS];
    }

    #[DataProvider('coerceProvider')]
    public function testExecuteWithCoerce(mixed $input, TestStatus $expected): void
    {
        $type = new EnumType(TestStatus::class, coerce: true);
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
        yield 'non-matching string' => ['UNKNOWN'];
        yield 'lowercase case name' => ['success'];
        yield 'empty string' => [''];
        yield 'int' => [42];
        yield 'float' => [3.14];
        yield 'bool true' => [true];
        yield 'bool false' => [false];
        yield 'backed value string' => ['active'];
        yield 'wrong enum instance' => [TestBackedStatus::ACTIVE];
    }

    #[DataProvider('coerceStillRejectsProvider')]
    public function testExecuteWithCoerceStillRejects(mixed $input): void
    {
        $type = new EnumType(TestStatus::class, coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Invalid value.', $context->issues[0]->message);
    }

    /**
     * @return iterable<string, array{mixed, TestBackedStatus|TestIntBacked}>
     */
    public static function backedEnumCoerceProvider(): iterable
    {
        yield 'string backed: case name ACTIVE' => ['ACTIVE', TestBackedStatus::ACTIVE];
        yield 'string backed: value active' => ['active', TestBackedStatus::ACTIVE];
        yield 'string backed: value inactive' => ['inactive', TestBackedStatus::INACTIVE];
        yield 'string backed: instance' => [TestBackedStatus::ACTIVE, TestBackedStatus::ACTIVE];
        yield 'int backed: value 1' => [1, TestIntBacked::LOW];
        yield 'int backed: value 2' => [2, TestIntBacked::HIGH];
        yield 'int backed: case name LOW' => ['LOW', TestIntBacked::LOW];
    }

    #[DataProvider('backedEnumCoerceProvider')]
    public function testBackedEnumCoerce(mixed $input, TestBackedStatus|TestIntBacked $expected): void
    {
        $type = new EnumType($expected::class, coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame($expected, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{class-string<TestBackedStatus|TestIntBacked>, mixed}>
     */
    public static function backedEnumCoerceStillRejectsProvider(): iterable
    {
        yield 'string backed: non-matching value' => [TestBackedStatus::class, 'unknown'];
        yield 'string backed: int value' => [TestBackedStatus::class, 42];
        yield 'string backed: null' => [TestBackedStatus::class, null];
        yield 'string backed: array' => [TestBackedStatus::class, []];
        yield 'int backed: non-matching int' => [TestIntBacked::class, 99];
        yield 'int backed: string value' => [TestIntBacked::class, '1'];
        yield 'int backed: null' => [TestIntBacked::class, null];
    }

    /**
     * @param class-string<TestBackedStatus|TestIntBacked> $enumClass
     */
    #[DataProvider('backedEnumCoerceStillRejectsProvider')]
    public function testBackedEnumCoerceStillRejects(string $enumClass, mixed $input): void
    {
        $type = new EnumType($enumClass, coerce: true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Invalid value.', $context->issues[0]->message);
    }
}
