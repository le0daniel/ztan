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
        yield 'wrong format' => ['Y-m-d', '15/01/2024', 'Invalid date format.'];
        yield 'empty string' => ['Y-m-d', '', 'Invalid date format.'];
        yield 'overflow day feb 30' => ['Y-m-d', '2024-02-30', 'Invalid date format.'];
        yield 'feb 29 non-leap year' => ['Y-m-d', '2023-02-29', 'Invalid date format.'];
        yield 'invalid month' => ['Y-m-d', '2024-13-01', 'Invalid date format.'];
        yield 'invalid day' => ['Y-m-d', '2024-01-32', 'Invalid date format.'];
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

    public function testAfterAccepts(): void
    {
        $type = (new DateTimeStringType('Y-m-d'))->after(new DateTimeImmutable('2024-01-01'));
        $context = new ValidationContext();

        $result = $type->execute('2024-06-15', $context);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame([], $context->issues);
    }

    public function testAfterRejects(): void
    {
        $type = (new DateTimeStringType('Y-m-d'))->after(new DateTimeImmutable('2024-06-01'));
        $context = new ValidationContext();

        $result = $type->execute('2024-01-15', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Date is too early.', $context->issues[0]->message);
    }

    public function testBeforeAccepts(): void
    {
        $type = (new DateTimeStringType('Y-m-d'))->before(new DateTimeImmutable('2024-06-01'));
        $context = new ValidationContext();

        $result = $type->execute('2024-01-15', $context);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame([], $context->issues);
    }

    public function testBeforeRejects(): void
    {
        $type = (new DateTimeStringType('Y-m-d'))->before(new DateTimeImmutable('2024-01-01'));
        $context = new ValidationContext();

        $result = $type->execute('2024-06-15', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Date is too late.', $context->issues[0]->message);
    }

    public function testBetweenAcceptsInRange(): void
    {
        $type = (new DateTimeStringType('Y-m-d'))->between(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31'),
        );
        $context = new ValidationContext();

        $result = $type->execute('2024-06-15', $context);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame([], $context->issues);
    }

    public function testBetweenAcceptsBoundaries(): void
    {
        $type = (new DateTimeStringType('Y-m-d H:i:s'))->between(
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-12-31 23:59:59'),
        );

        $startContext = new ValidationContext();
        $startResult = $type->execute('2024-01-01 00:00:00', $startContext);
        self::assertInstanceOf(DateTimeImmutable::class, $startResult);
        self::assertSame([], $startContext->issues);

        $endContext = new ValidationContext();
        $endResult = $type->execute('2024-12-31 23:59:59', $endContext);
        self::assertInstanceOf(DateTimeImmutable::class, $endResult);
        self::assertSame([], $endContext->issues);
    }

    public function testBetweenRejectsOutside(): void
    {
        $type = (new DateTimeStringType('Y-m-d'))->between(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31'),
        );
        $context = new ValidationContext();

        $result = $type->execute('2025-06-15', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertNotEmpty($context->issues);
    }

    public function testPastWithClock(): void
    {
        $clock = new class implements \Psr\Clock\ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2024-06-01');
            }
        };

        $type = (new DateTimeStringType('Y-m-d'))->past($clock);
        $context = new ValidationContext();

        $result = $type->execute('2024-01-15', $context);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame([], $context->issues);
    }

    public function testPastRejectsWithClock(): void
    {
        $clock = new class implements \Psr\Clock\ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2024-06-01');
            }
        };

        $type = (new DateTimeStringType('Y-m-d'))->past($clock);
        $context = new ValidationContext();

        $result = $type->execute('2024-12-15', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    public function testFutureWithClock(): void
    {
        $clock = new class implements \Psr\Clock\ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2024-06-01');
            }
        };

        $type = (new DateTimeStringType('Y-m-d'))->future($clock);
        $context = new ValidationContext();

        $result = $type->execute('2024-12-15', $context);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame([], $context->issues);
    }

    public function testFutureRejectsWithClock(): void
    {
        $clock = new class implements \Psr\Clock\ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2024-06-01');
            }
        };

        $type = (new DateTimeStringType('Y-m-d'))->future($clock);
        $context = new ValidationContext();

        $result = $type->execute('2024-01-15', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    public function testImmutability(): void
    {
        $original = new DateTimeStringType('Y-m-d');
        $after = $original->after(new DateTimeImmutable('2024-01-01'));

        $context = new ValidationContext();
        $result = $original->execute('2023-01-01', $context);

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame([], $context->issues);
        self::assertNotSame($original, $after);
    }
}
