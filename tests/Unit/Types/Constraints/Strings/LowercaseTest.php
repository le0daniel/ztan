<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Types\Pipe\Strings\Lowercase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LowercaseTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function lowercaseProvider(): iterable
    {
        yield 'uppercase' => ['HELLO', 'hello'];
        yield 'mixed case' => ['HeLLo WoRLd', 'hello world'];
        yield 'already lowercase' => ['hello', 'hello'];
        yield 'empty string' => ['', ''];
        yield 'unicode' => ['ÜBER', 'über'];
        yield 'numbers unchanged' => ['ABC123', 'abc123'];
    }

    #[DataProvider('lowercaseProvider')]
    public function testLowercase(string $input, string $expected): void
    {
        $pipe = new Lowercase();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($expected, $result);
        self::assertSame([], $context->issues);
    }
}
