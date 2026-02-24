<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Types\Pipe\Strings\Trim;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrimTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function trimProvider(): iterable
    {
        yield 'leading spaces' => ['  hello', 'hello'];
        yield 'trailing spaces' => ['hello  ', 'hello'];
        yield 'both sides' => ['  hello  ', 'hello'];
        yield 'tabs and newlines' => ["\t\nhello\n\t", 'hello'];
        yield 'no whitespace' => ['hello', 'hello'];
        yield 'empty string' => ['', ''];
        yield 'only whitespace' => ['   ', ''];
    }

    #[DataProvider('trimProvider')]
    public function testTrim(string $input, string $expected): void
    {
        $pipe = new Trim();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($expected, $result);
        self::assertSame([], $context->issues);
    }
}
