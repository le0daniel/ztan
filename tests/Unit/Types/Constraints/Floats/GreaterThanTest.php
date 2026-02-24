<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Floats;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Floats\GreaterThan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GreaterThanTest extends TestCase
{
    /**
     * @return iterable<string, array{float, float}>
     */
    public static function strictValidProvider(): iterable
    {
        yield 'above threshold' => [5.5, 3.5];
        yield 'well above threshold' => [100.0, 3.5];
        yield 'above zero' => [0.1, 0.0];
        yield 'above negative' => [0.0, -2.5];
    }

    #[DataProvider('strictValidProvider')]
    public function testStrictAcceptsValidValues(float $input, float $threshold): void
    {
        $pipe = new GreaterThan($threshold);
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
        yield 'below threshold' => [2.5, 3.5];
        yield 'zero equal' => [0.0, 0.0];
        yield 'negative below' => [-2.5, -1.5];
    }

    #[DataProvider('strictInvalidProvider')]
    public function testStrictRejectsInvalidValues(float $input, float $threshold): void
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
     * @return iterable<string, array{float, float}>
     */
    public static function includingValidProvider(): iterable
    {
        yield 'above threshold' => [5.5, 3.5];
        yield 'equal to threshold' => [3.5, 3.5];
        yield 'zero equal' => [0.0, 0.0];
        yield 'above negative' => [0.0, -2.5];
    }

    #[DataProvider('includingValidProvider')]
    public function testIncludingAcceptsValidValues(float $input, float $threshold): void
    {
        $pipe = new GreaterThan($threshold, true);
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
        yield 'below threshold' => [2.5, 3.5];
        yield 'well below threshold' => [0.0, 3.5];
        yield 'negative below' => [-2.5, -1.5];
    }

    #[DataProvider('includingInvalidProvider')]
    public function testIncludingRejectsInvalidValues(float $input, float $threshold): void
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
        $pipe = new GreaterThan(5.0);
        $context = new ValidationContext();

        $result = $pipe->execute(5.0, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }
}
