<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Scalars;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Scalars\EnumType;
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
        self::assertCount(1, $context->issues[''] ?? []);
        self::assertSame('Invalid value.', $context->issues[''][0]->message);
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
        self::assertCount(1, $context->issues[''] ?? []);
        self::assertSame('Invalid value.', $context->issues[''][0]->message);
    }
}
