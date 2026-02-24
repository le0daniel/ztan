<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Enums;

use Le0daniel\Assertions\Data\Exceptions\InvalidSchemaException;
use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Enums\Not;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum NotTestColor
{
    case Red;
    case Green;
    case Blue;
    case Yellow;
}

final class NotTest extends TestCase
{
    /**
     * @return iterable<string, array{NotTestColor, list<NotTestColor>}>
     */
    public static function validProvider(): iterable
    {
        yield 'not in single disallowed' => [NotTestColor::Green, [NotTestColor::Red]];
        yield 'not in two disallowed' => [NotTestColor::Blue, [NotTestColor::Red, NotTestColor::Green]];
        yield 'not in three disallowed' => [NotTestColor::Yellow, [NotTestColor::Red, NotTestColor::Green, NotTestColor::Blue]];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsNonDisallowedCases(NotTestColor $input, array $cases): void
    {
        $pipe = new Not($cases);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{NotTestColor, list<NotTestColor>}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'in single disallowed' => [NotTestColor::Red, [NotTestColor::Red]];
        yield 'first of two disallowed' => [NotTestColor::Red, [NotTestColor::Red, NotTestColor::Green]];
        yield 'second of two disallowed' => [NotTestColor::Green, [NotTestColor::Red, NotTestColor::Green]];
        yield 'in all disallowed' => [NotTestColor::Blue, [NotTestColor::Red, NotTestColor::Green, NotTestColor::Blue]];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsDisallowedCases(NotTestColor $input, array $cases): void
    {
        $pipe = new Not($cases);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is not allowed.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
        self::assertSame(
            array_map(fn(NotTestColor $case) => $case->name, $cases),
            $context->issues[0]->metadata['disallowed'],
        );
    }

    public function testThrowsOnEmptyCases(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('At least one case must be provided.');

        new Not([]);
    }
}
