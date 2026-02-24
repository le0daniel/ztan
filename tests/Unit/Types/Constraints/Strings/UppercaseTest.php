<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Types\Pipe\Strings\Uppercase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UppercaseTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function uppercaseProvider(): iterable
    {
        yield 'lowercase' => ['hello', 'HELLO'];
        yield 'mixed case' => ['HeLLo WoRLd', 'HELLO WORLD'];
        yield 'already uppercase' => ['HELLO', 'HELLO'];
        yield 'empty string' => ['', ''];
        yield 'unicode' => ['über', 'ÜBER'];
        yield 'numbers unchanged' => ['abc123', 'ABC123'];
    }

    #[DataProvider('uppercaseProvider')]
    public function testUppercase(string $input, string $expected): void
    {
        $pipe = new Uppercase();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($expected, $result);
        self::assertSame([], $context->issues);
    }
}
