<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\DateTimes;

use DateTimeImmutable;
use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\DateTimes\After;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AfterTest extends TestCase
{
    /**
     * @return iterable<string, array{DateTimeImmutable, DateTimeImmutable}>
     */
    public static function strictValidProvider(): iterable
    {
        yield 'after threshold' => [new DateTimeImmutable('2024-06-01'), new DateTimeImmutable('2024-01-01')];
        yield 'one second after' => [new DateTimeImmutable('2024-01-01 00:00:01'), new DateTimeImmutable('2024-01-01 00:00:00')];
    }

    #[DataProvider('strictValidProvider')]
    public function testStrictAcceptsValidValues(DateTimeImmutable $input, DateTimeImmutable $threshold): void
    {
        $pipe = new After($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertEquals($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{DateTimeImmutable, DateTimeImmutable}>
     */
    public static function strictInvalidProvider(): iterable
    {
        yield 'equal to threshold' => [new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-01')];
        yield 'before threshold' => [new DateTimeImmutable('2023-06-01'), new DateTimeImmutable('2024-01-01')];
    }

    #[DataProvider('strictInvalidProvider')]
    public function testStrictRejectsInvalidValues(DateTimeImmutable $input, DateTimeImmutable $threshold): void
    {
        $pipe = new After($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Date is too early.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertFalse($context->issues[0]->metadata['including']);
    }

    /**
     * @return iterable<string, array{DateTimeImmutable, DateTimeImmutable}>
     */
    public static function includingValidProvider(): iterable
    {
        yield 'after threshold' => [new DateTimeImmutable('2024-06-01'), new DateTimeImmutable('2024-01-01')];
        yield 'equal to threshold' => [new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-01')];
    }

    #[DataProvider('includingValidProvider')]
    public function testIncludingAcceptsValidValues(DateTimeImmutable $input, DateTimeImmutable $threshold): void
    {
        $pipe = new After($threshold, true);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertEquals($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{DateTimeImmutable, DateTimeImmutable}>
     */
    public static function includingInvalidProvider(): iterable
    {
        yield 'before threshold' => [new DateTimeImmutable('2023-06-01'), new DateTimeImmutable('2024-01-01')];
        yield 'one second before' => [new DateTimeImmutable('2023-12-31 23:59:59'), new DateTimeImmutable('2024-01-01 00:00:00')];
    }

    #[DataProvider('includingInvalidProvider')]
    public function testIncludingRejectsInvalidValues(DateTimeImmutable $input, DateTimeImmutable $threshold): void
    {
        $pipe = new After($threshold, true);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Date is too early.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertTrue($context->issues[0]->metadata['including']);
    }

    public function testDefaultIncludingIsFalse(): void
    {
        $pipe = new After(new DateTimeImmutable('2024-01-01'));
        $context = new ValidationContext();

        $result = $pipe->execute(new DateTimeImmutable('2024-01-01'), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    public function testClosureThreshold(): void
    {
        $threshold = new DateTimeImmutable('2024-01-01');
        $pipe = new After(fn() => $threshold);
        $context = new ValidationContext();

        $result = $pipe->execute(new DateTimeImmutable('2024-06-01'), $context);

        self::assertEquals(new DateTimeImmutable('2024-06-01'), $result);
        self::assertSame([], $context->issues);
    }
}
