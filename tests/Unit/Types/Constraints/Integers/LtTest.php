<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Integers;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Integers\Lt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LtTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function validProvider(): iterable
    {
        yield 'below threshold' => [2, 3];
        yield 'well below threshold' => [0, 3];
        yield 'below zero' => [-1, 0];
        yield 'negative below negative' => [-5, -1];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidValues(int $input, int $threshold): void
    {
        $pipe = new Lt($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'equal to threshold' => [3, 3];
        yield 'above threshold' => [5, 3];
        yield 'zero equal' => [0, 0];
        yield 'positive above negative' => [1, -1];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidValues(int $input, int $threshold): void
    {
        $pipe = new Lt($threshold);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is too large.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($threshold, $context->issues[0]->metadata['threshold']);
        self::assertSame($input, $context->issues[0]->metadata['actual']);
    }
}
