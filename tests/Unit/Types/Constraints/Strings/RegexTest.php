<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Strings\Regex;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegexTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validProvider(): iterable
    {
        yield 'digits only' => ['12345', '/^\d+$/'];
        yield 'email-like' => ['test@example.com', '/^.+@.+\..+$/'];
        yield 'alphanumeric' => ['abc123', '/^[a-z0-9]+$/i'];
        yield 'partial match' => ['hello world', '/world/'];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsMatchingStrings(string $input, string $pattern): void
    {
        $pipe = new Regex($pattern);
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
        yield 'letters for digits pattern' => ['abc', '/^\d+$/'];
        yield 'missing at sign' => ['test.example.com', '/^.+@.+\..+$/'];
        yield 'special chars' => ['hello!', '/^[a-z0-9]+$/'];
        yield 'empty string' => ['', '/^.+$/'];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsNonMatchingStrings(string $input, string $pattern): void
    {
        $pipe = new Regex($pattern);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String does not match the expected pattern.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame($pattern, $context->issues[0]->metadata['pattern']);
    }
}
