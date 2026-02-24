<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Strings\NotEmpty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NotEmptyTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validProvider(): iterable
    {
        yield 'single character' => ['a'];
        yield 'word' => ['hello'];
        yield 'whitespace' => [' '];
        yield 'newline' => ["\n"];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsNonEmptyStrings(string $input): void
    {
        $pipe = new NotEmpty();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    public function testRejectsEmptyString(): void
    {
        $pipe = new NotEmpty();
        $context = new ValidationContext();

        $result = $pipe->execute('', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String must not be empty.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame('', $context->issues[0]->received);
    }
}
