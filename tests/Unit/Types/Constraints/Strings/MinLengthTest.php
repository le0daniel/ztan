<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Strings\MinLength;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MinLengthTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function validProvider(): iterable
    {
        yield 'exact length inclusive' => ['abc', 3, true];
        yield 'longer than min inclusive' => ['abcd', 3, true];
        yield 'longer than min exclusive' => ['abcd', 3, false];
        yield 'zero length inclusive' => ['', 0, true];
        yield 'unicode exact' => ['über', 4, true];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidStrings(string $input, int $length, bool $including): void
    {
        $pipe = new MinLength($length, $including);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'too short inclusive' => ['ab', 3, true];
        yield 'exact length exclusive' => ['abc', 3, false];
        yield 'empty string' => ['', 1, true];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsTooShortStrings(string $input, int $length, bool $including): void
    {
        $pipe = new MinLength($length, $including);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String is too short.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($length, $context->issues[0]->metadata['min_length']);
        self::assertSame($including, $context->issues[0]->metadata['including']);
        self::assertSame(mb_strlen($input), $context->issues[0]->metadata['actual_length']);
    }

    public function testDefaultIncludingIsTrue(): void
    {
        $pipe = new MinLength(3);
        $context = new ValidationContext();

        $result = $pipe->execute('abc', $context);

        self::assertSame('abc', $result);
    }
}
