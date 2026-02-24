<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Constraints\Integers;

use Le0daniel\Ztan\Data\IssueType;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Integers\GreaterThan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GreaterThanTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function strictValidProvider(): iterable
    {
        yield 'above threshold' => [5, 3];
        yield 'well above threshold' => [100, 3];
        yield 'above zero' => [1, 0];
        yield 'above negative' => [0, -1];
    }

    #[DataProvider('strictValidProvider')]
    public function testStrictAcceptsValidValues(int $input, int $threshold): void
    {
        $pipe = new GreaterThan($threshold);
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
        yield 'below threshold' => [2, 3];
        yield 'zero equal' => [0, 0];
        yield 'negative below' => [-2, -1];
    }

    #[DataProvider('strictInvalidProvider')]
    public function testStrictRejectsInvalidValues(int $input, int $threshold): void
    {
        $pipe = new GreaterThan($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is too small.', $context->issues[0]->message);
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
        yield 'above threshold' => [5, 3];
        yield 'equal to threshold' => [3, 3];
        yield 'zero equal' => [0, 0];
        yield 'above negative' => [0, -1];
    }

    #[DataProvider('includingValidProvider')]
    public function testIncludingAcceptsValidValues(int $input, int $threshold): void
    {
        $pipe = new GreaterThan($threshold, true);
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
        yield 'below threshold' => [2, 3];
        yield 'well below threshold' => [0, 3];
        yield 'negative below' => [-2, -1];
    }

    #[DataProvider('includingInvalidProvider')]
    public function testIncludingRejectsInvalidValues(int $input, int $threshold): void
    {
        $pipe = new GreaterThan($threshold, true);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is too small.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($threshold, $context->issues[0]->metadata['threshold']);
        self::assertTrue($context->issues[0]->metadata['including']);
        self::assertSame($input, $context->issues[0]->metadata['actual']);
    }

    public function testDefaultIncludingIsFalse(): void
    {
        $pipe = new GreaterThan(5);
        $context = new ValidationContext();

        $result = $pipe->execute(5, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }
}
