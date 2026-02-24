<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Integers;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Integers\Negative;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NegativeTest extends TestCase
{
    /**
     * @return iterable<string, array{int}>
     */
    public static function validProvider(): iterable
    {
        yield 'minus one' => [-1];
        yield 'large negative' => [-1000];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsNegativeValues(int $input): void
    {
        $pipe = new Negative();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'positive' => [1];
        yield 'large positive' => [1000];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsNonNegativeValues(int $input): void
    {
        $pipe = new Negative();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is too large.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
    }
}
