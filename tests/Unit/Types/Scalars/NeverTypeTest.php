<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Scalars;

use Le0daniel\Ztan\Data\IssueType;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Scalars\NeverType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class NeverTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function allValuesProvider(): iterable
    {
        yield 'string' => ['hello'];
        yield 'empty string' => [''];
        yield 'int' => [42];
        yield 'zero' => [0];
        yield 'float' => [3.14];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'object' => [new stdClass()];
    }

    #[DataProvider('allValuesProvider')]
    public function testRejectsAllValues(mixed $input): void
    {
        $type = new NeverType();
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Should never be reached.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
    }
}
