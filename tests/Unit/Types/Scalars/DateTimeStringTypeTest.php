<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Scalars;

use DateTimeImmutable;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Scalars\DateTimeStringType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateTimeStringTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validDateTimeProvider(): iterable
    {
        yield 'Y-m-d' => ['Y-m-d', '2024-01-15'];
        yield 'Y-m-d H:i:s' => ['Y-m-d H:i:s', '2024-01-15 13:45:30'];
        yield 'd/m/Y' => ['d/m/Y', '15/01/2024'];
        yield 'H:i:s' => ['H:i:s', '13:45:30'];
        yield 'leap day on leap year' => ['Y-m-d', '2024-02-29'];
    }

    #[DataProvider('validDateTimeProvider')]
    public function testExecuteAcceptsValid(string $format, string $input): void
    {
        $type = new DateTimeStringType($format);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame($input, $result->format($format));
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{string, mixed, string}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'int' => ['Y-m-d', 42, 'Expected string.'];
        yield 'float' => ['Y-m-d', 3.14, 'Expected string.'];
        yield 'true' => ['Y-m-d', true, 'Expected string.'];
        yield 'false' => ['Y-m-d', false, 'Expected string.'];
        yield 'null' => ['Y-m-d', null, 'Expected string.'];
        yield 'array' => ['Y-m-d', [], 'Expected string.'];
        yield 'object' => ['Y-m-d', new \stdClass(), 'Expected string.'];
        yield 'wrong format' => ['Y-m-d', '15/01/2024', 'Expected datetime string matching format: Y-m-d.'];
        yield 'empty string' => ['Y-m-d', '', 'Expected datetime string matching format: Y-m-d.'];
        yield 'overflow day feb 30' => ['Y-m-d', '2024-02-30', 'Expected datetime string matching format: Y-m-d.'];
        yield 'feb 29 non-leap year' => ['Y-m-d', '2023-02-29', 'Expected datetime string matching format: Y-m-d.'];
        yield 'invalid month' => ['Y-m-d', '2024-13-01', 'Expected datetime string matching format: Y-m-d.'];
        yield 'invalid day' => ['Y-m-d', '2024-01-32', 'Expected datetime string matching format: Y-m-d.'];
    }

    #[DataProvider('invalidInputProvider')]
    public function testExecuteRejectsInvalid(string $format, mixed $input, string $expectedMessage): void
    {
        $type = new DateTimeStringType($format);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame($expectedMessage, $context->issues[0]->message);
    }
}
