<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Integers;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Integers\LowerThan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LowerThanTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function strictValidProvider(): iterable
    {
        yield 'below threshold' => [2, 3];
        yield 'well below threshold' => [0, 100];
        yield 'below zero' => [-1, 0];
        yield 'below negative' => [-2, -1];
    }

    #[DataProvider('strictValidProvider')]
    public function testStrictAcceptsValidValues(int $input, int $threshold): void
    {
        $pipe = new LowerThan($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function strictInvalidProvider(): iterable
    {
        yield 'equal to threshold' => [3, 3];
        yield 'above threshold' => [4, 3];
        yield 'zero equal' => [0, 0];
        yield 'negative above' => [-1, -2];
    }

    #[DataProvider('strictInvalidProvider')]
    public function testStrictRejectsInvalidValues(int $input, int $threshold): void
    {
        $pipe = new LowerThan($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is too large.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($threshold, $context->issues[0]->metadata['threshold']);
        self::assertFalse($context->issues[0]->metadata['including']);
        self::assertSame($input, $context->issues[0]->metadata['actual']);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function includingValidProvider(): iterable
    {
        yield 'below threshold' => [2, 3];
        yield 'equal to threshold' => [3, 3];
        yield 'zero equal' => [0, 0];
        yield 'below negative' => [-2, -1];
    }

    #[DataProvider('includingValidProvider')]
    public function testIncludingAcceptsValidValues(int $input, int $threshold): void
    {
        $pipe = new LowerThan($threshold, true);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function includingInvalidProvider(): iterable
    {
        yield 'above threshold' => [4, 3];
        yield 'well above threshold' => [100, 3];
        yield 'negative above' => [-1, -2];
    }

    #[DataProvider('includingInvalidProvider')]
    public function testIncludingRejectsInvalidValues(int $input, int $threshold): void
    {
        $pipe = new LowerThan($threshold, true);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is too large.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($threshold, $context->issues[0]->metadata['threshold']);
        self::assertTrue($context->issues[0]->metadata['including']);
        self::assertSame($input, $context->issues[0]->metadata['actual']);
    }

    public function testDefaultIncludingIsFalse(): void
    {
        $pipe = new LowerThan(5);
        $context = new ValidationContext();

        $result = $pipe->execute(5, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }
}
