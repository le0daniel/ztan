<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Integers;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Integers\Range;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RangeTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, int, bool}>
     */
    public static function validProvider(): iterable
    {
        yield 'within range inclusive' => [5, 1, 10, true];
        yield 'at min inclusive' => [1, 1, 10, true];
        yield 'at max inclusive' => [10, 1, 10, true];
        yield 'within range exclusive' => [5, 1, 10, false];
        yield 'negative range' => [-3, -5, -1, true];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidValues(int $input, int $min, int $max, bool $including): void
    {
        $pipe = new Range($min, $max, $including);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{int, int, int, bool}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'below range inclusive' => [0, 1, 10, true];
        yield 'above range inclusive' => [11, 1, 10, true];
        yield 'at min exclusive' => [1, 1, 10, false];
        yield 'at max exclusive' => [10, 1, 10, false];
        yield 'below negative range' => [-6, -5, -1, true];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidValues(int $input, int $min, int $max, bool $including): void
    {
        $pipe = new Range($min, $max, $including);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is out of range.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($min, $context->issues[0]->metadata['min']);
        self::assertSame($max, $context->issues[0]->metadata['max']);
        self::assertSame($including, $context->issues[0]->metadata['including']);
        self::assertSame($input, $context->issues[0]->metadata['actual']);
    }

    public function testDefaultIncludingIsTrue(): void
    {
        $pipe = new Range(1, 10);
        $context = new ValidationContext();

        self::assertSame(1, $pipe->execute(1, $context));
        self::assertSame(10, $pipe->execute(10, $context));
        self::assertSame([], $context->issues);
    }
}
