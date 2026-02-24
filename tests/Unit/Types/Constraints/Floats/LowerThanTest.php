<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Constraints\Floats;

use Le0daniel\Ztan\Data\IssueType;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Floats\LowerThan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LowerThanTest extends TestCase
{
    /**
     * @return iterable<string, array{float, float}>
     */
    public static function strictValidProvider(): iterable
    {
        yield 'below threshold' => [2.5, 3.5];
        yield 'well below threshold' => [0.0, 100.0];
        yield 'below zero' => [-0.1, 0.0];
        yield 'below negative' => [-2.5, -1.5];
    }

    #[DataProvider('strictValidProvider')]
    public function testStrictAcceptsValidValues(float $input, float $threshold): void
    {
        $pipe = new LowerThan($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function strictInvalidProvider(): iterable
    {
        yield 'equal to threshold' => [3.5, 3.5];
        yield 'above threshold' => [4.5, 3.5];
        yield 'zero equal' => [0.0, 0.0];
        yield 'negative above' => [-1.5, -2.5];
    }

    #[DataProvider('strictInvalidProvider')]
    public function testStrictRejectsInvalidValues(float $input, float $threshold): void
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
     * @return iterable<string, array{float, float}>
     */
    public static function includingValidProvider(): iterable
    {
        yield 'below threshold' => [2.5, 3.5];
        yield 'equal to threshold' => [3.5, 3.5];
        yield 'zero equal' => [0.0, 0.0];
        yield 'below negative' => [-2.5, -1.5];
    }

    #[DataProvider('includingValidProvider')]
    public function testIncludingAcceptsValidValues(float $input, float $threshold): void
    {
        $pipe = new LowerThan($threshold, true);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function includingInvalidProvider(): iterable
    {
        yield 'above threshold' => [4.5, 3.5];
        yield 'well above threshold' => [100.0, 3.5];
        yield 'negative above' => [-1.5, -2.5];
    }

    #[DataProvider('includingInvalidProvider')]
    public function testIncludingRejectsInvalidValues(float $input, float $threshold): void
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
        $pipe = new LowerThan(5.0);
        $context = new ValidationContext();

        $result = $pipe->execute(5.0, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }
}
