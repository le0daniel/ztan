<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Ztan\Data\IssueType;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Strings\StartsWith;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StartsWithTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validProvider(): iterable
    {
        yield 'simple prefix' => ['hello world', 'hello'];
        yield 'exact match' => ['hello', 'hello'];
        yield 'empty prefix' => ['hello', ''];
        yield 'single char' => ['abc', 'a'];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsMatchingStrings(string $input, string $prefix): void
    {
        $pipe = new StartsWith($prefix);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'wrong prefix' => ['hello world', 'world'];
        yield 'case sensitive' => ['Hello', 'hello'];
        yield 'empty string' => ['', 'a'];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsNonMatchingStrings(string $input, string $prefix): void
    {
        $pipe = new StartsWith($prefix);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String does not start with the expected prefix.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($prefix, $context->issues[0]->metadata['prefix']);
    }
}
