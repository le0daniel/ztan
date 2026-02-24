<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Ztan\Data\IssueType;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Strings\IsEmpty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IsEmptyTest extends TestCase
{
    public function testAcceptsEmptyString(): void
    {
        $pipe = new IsEmpty();
        $context = new ValidationContext();

        $result = $pipe->execute('', $context);

        self::assertSame('', $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'single character' => ['a'];
        yield 'word' => ['hello'];
        yield 'whitespace' => [' '];
        yield 'newline' => ["\n"];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsNonEmptyStrings(string $input): void
    {
        $pipe = new IsEmpty();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String must be empty.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
    }
}
