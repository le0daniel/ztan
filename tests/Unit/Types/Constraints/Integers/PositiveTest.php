<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Integers;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Integers\Positive;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PositiveTest extends TestCase
{
    /**
     * @return iterable<string, array{int}>
     */
    public static function validProvider(): iterable
    {
        yield 'one' => [1];
        yield 'large positive' => [1000];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsPositiveValues(int $input): void
    {
        $pipe = new Positive();
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
        yield 'negative' => [-1];
        yield 'large negative' => [-1000];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsNonPositiveValues(int $input): void
    {
        $pipe = new Positive();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is too small.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
    }
}
