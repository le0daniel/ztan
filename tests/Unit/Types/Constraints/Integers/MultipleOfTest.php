<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Integers;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Integers\MultipleOf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MultipleOfTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int}>
     */
    public static function validProvider(): iterable
    {
        yield 'exact multiple' => [6, 3];
        yield 'zero is multiple of anything' => [0, 5];
        yield 'negative multiple' => [-9, 3];
        yield 'value equals divisor' => [7, 7];
        yield 'even number' => [10, 2];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsMultiples(int $input, int $divisor): void
    {
        $pipe = new MultipleOf($divisor);
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
        yield 'not a multiple' => [7, 3];
        yield 'odd not multiple of 2' => [5, 2];
        yield 'negative not multiple' => [-7, 3];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsNonMultiples(int $input, int $divisor): void
    {
        $pipe = new MultipleOf($divisor);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is not a multiple of the expected number.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($divisor, $context->issues[0]->metadata['divisor']);
        self::assertSame($input, $context->issues[0]->metadata['actual']);
    }
}
