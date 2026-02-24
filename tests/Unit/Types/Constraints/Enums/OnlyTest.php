<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Enums;

use Le0daniel\Assertions\Data\Exceptions\InvalidSchemaException;
use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Enums\Only;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum OnlyTestSuit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}

final class OnlyTest extends TestCase
{
    /**
     * @return iterable<string, array{OnlyTestSuit, list<OnlyTestSuit>}>
     */
    public static function validProvider(): iterable
    {
        yield 'single allowed case' => [OnlyTestSuit::Hearts, [OnlyTestSuit::Hearts]];
        yield 'first of two allowed' => [OnlyTestSuit::Hearts, [OnlyTestSuit::Hearts, OnlyTestSuit::Diamonds]];
        yield 'second of two allowed' => [OnlyTestSuit::Diamonds, [OnlyTestSuit::Hearts, OnlyTestSuit::Diamonds]];
        yield 'all cases allowed' => [OnlyTestSuit::Spades, [OnlyTestSuit::Hearts, OnlyTestSuit::Diamonds, OnlyTestSuit::Clubs, OnlyTestSuit::Spades]];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsAllowedCases(OnlyTestSuit $input, array $cases): void
    {
        $pipe = new Only($cases);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{OnlyTestSuit, list<OnlyTestSuit>}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'not in single case list' => [OnlyTestSuit::Clubs, [OnlyTestSuit::Hearts]];
        yield 'not in two case list' => [OnlyTestSuit::Spades, [OnlyTestSuit::Hearts, OnlyTestSuit::Diamonds]];
        yield 'excluded from partial list' => [OnlyTestSuit::Hearts, [OnlyTestSuit::Diamonds, OnlyTestSuit::Clubs]];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsDisallowedCases(OnlyTestSuit $input, array $cases): void
    {
        $pipe = new Only($cases);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is not allowed.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame(
            array_map(fn(OnlyTestSuit $case) => $case->name, $cases),
            $context->issues[0]->metadata['allowed'],
        );
    }

    public function testThrowsOnEmptyCases(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('At least one case must be provided.');

        new Only([]);
    }
}
