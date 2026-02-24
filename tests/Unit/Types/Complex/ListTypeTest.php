<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Complex;

use ArrayIterator;
use IteratorAggregate;
use Le0daniel\Assertions\Data\ParseError;
use Le0daniel\Assertions\Data\ParseSuccess;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\CatchType;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\ListType;
use Le0daniel\Assertions\Types\Scalars\IntType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;
use stdClass;
use Traversable;

final class ListTypeTest extends TestCase
{
    public function testValidListOfStrings(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(['a', 'b', 'c'], $context);

        self::assertSame(['a', 'b', 'c'], $result);
        self::assertSame([], $context->issues);
    }

    public function testEmptyListPasses(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute([], $context);

        self::assertSame([], $result);
        self::assertSame([], $context->issues);
    }

    public function testNonArrayRejected(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute('not-an-array', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected an iterable list.', $context->issues[0]->message);
    }

    public function testNonListArrayRejected(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(['a' => 'b'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected a list, got non-sequential keys.', $context->issues[0]->message);
    }

    public function testNonSequentialIntKeysRejected(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute([2 => 'x', 5 => 'y'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected a list, got non-sequential keys.', $context->issues[0]->message);
    }

    public function testInvalidElementAtIndex(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(['valid', 123], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('1', $context->issues[0]->getPathAsString());
    }

    public function testMultipleInvalidElements(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute([123, 'valid', 456], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(2, $context->issues);
        self::assertSame('0', $context->issues[0]->getPathAsString());
        self::assertSame('2', $context->issues[1]->getPathAsString());
    }

    public function testNestedListOfArrayShapePathTracking(): void
    {
        $type = new ListType(new ArrayShapeType([
            'name' => new StringType(),
        ]));
        $context = new ValidationContext();

        $result = $type->execute([
            ['name' => 123],
        ], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('0.name', $context->issues[0]->getPathAsString());
    }

    public function testCompositionWithCatchType(): void
    {
        $type = new ListType(new CatchType(new StringType(), null));

        $result = $type->safeParse(['valid', 123, 'also-valid']);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertTrue($result->isPartial());
        self::assertSame(['valid', null, 'also-valid'], $result->data);
        self::assertNotEmpty($result->issues);
    }

    public function testParseEmptyArray(): void
    {
        $type = new ListType(new StringType());

        $result = $type->parse([]);

        self::assertSame([], $result);
    }

    public function testSafeParseSuccess(): void
    {
        $type = new ListType(new StringType());

        $result = $type->safeParse(['a', 'b']);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertSame(['a', 'b'], $result->data);
        self::assertFalse($result->isPartial());
    }

    public function testSafeParseError(): void
    {
        $type = new ListType(new StringType());

        $result = $type->safeParse([123]);

        self::assertInstanceOf(ParseError::class, $result);
        self::assertNotEmpty($result->issues);
    }

    public function testArrayIteratorWithSequentialValues(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator(['a', 'b', 'c']), $context);

        self::assertSame(['a', 'b', 'c'], $result);
        self::assertSame([], $context->issues);
    }

    public function testGeneratorWithSequentialYields(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $generator = (function () {
            yield 'x';
            yield 'y';
        })();

        $result = $type->execute($generator, $context);

        self::assertSame(['x', 'y'], $result);
        self::assertSame([], $context->issues);
    }

    public function testEmptyArrayIterator(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator([]), $context);

        self::assertSame([], $result);
        self::assertSame([], $context->issues);
    }

    public function testIteratorWithNonSequentialIntKeys(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator([2 => 'a', 5 => 'b']), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected a list, got non-sequential keys.', $context->issues[0]->message);
    }

    public function testIteratorWithStringKeys(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator(['key' => 'value']), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected a list, got non-sequential keys.', $context->issues[0]->message);
    }

    public function testIteratorWithInvalidElementValues(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator(['valid', 123, 456]), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(2, $context->issues);
        self::assertSame('1', $context->issues[0]->getPathAsString());
        self::assertSame('2', $context->issues[1]->getPathAsString());
    }

    public function testIteratorAggregateObject(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $iterable = new class implements IteratorAggregate {
            public function getIterator(): Traversable
            {
                return new ArrayIterator(['a', 'b']);
            }
        };

        $result = $type->execute($iterable, $context);

        self::assertSame(['a', 'b'], $result);
        self::assertSame([], $context->issues);
    }

    public function testNonIterableObjectRejected(): void
    {
        $type = new ListType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new stdClass(), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected an iterable list.', $context->issues[0]->message);
    }

    public function testNonEmptyRejectsEmptyList(): void
    {
        $type = (new ListType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute([], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too few items.', $context->issues[0]->message);
    }

    public function testNonEmptyAcceptsSingleItem(): void
    {
        $type = (new ListType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute(['a'], $context);

        self::assertSame(['a'], $result);
        self::assertSame([], $context->issues);
    }

    public function testMinItemsBoundary(): void
    {
        $type = (new ListType(new StringType()))->minItems(2);
        $context = new ValidationContext();

        // 1 item should fail
        $result = $type->execute(['a'], $context);
        self::assertSame(Value::INVALID, $result);

        // 2 items should pass
        $context2 = new ValidationContext();
        $result2 = $type->execute(['a', 'b'], $context2);
        self::assertSame(['a', 'b'], $result2);
    }

    public function testMaxItemsBoundary(): void
    {
        $type = (new ListType(new StringType()))->maxItems(3);
        $context = new ValidationContext();

        // 4 items should fail
        $result = $type->execute(['a', 'b', 'c', 'd'], $context);
        self::assertSame(Value::INVALID, $result);

        // 3 items should pass
        $context2 = new ValidationContext();
        $result2 = $type->execute(['a', 'b', 'c'], $context2);
        self::assertSame(['a', 'b', 'c'], $result2);
    }

    public function testLengthAcceptsExactCount(): void
    {
        $type = (new ListType(new StringType()))->length(3);
        $context = new ValidationContext();

        $result = $type->execute(['a', 'b', 'c'], $context);

        self::assertSame(['a', 'b', 'c'], $result);
        self::assertSame([], $context->issues);
    }

    public function testLengthRejectsDifferentCounts(): void
    {
        $type = (new ListType(new StringType()))->length(3);

        $context1 = new ValidationContext();
        self::assertSame(Value::INVALID, $type->execute(['a', 'b'], $context1));

        $context2 = new ValidationContext();
        self::assertSame(Value::INVALID, $type->execute(['a', 'b', 'c', 'd'], $context2));
    }

    public function testConstraintsComposeWithElementValidation(): void
    {
        $type = (new ListType(new IntType()))->minItems(1);
        $context = new ValidationContext();

        // Invalid elements should fail before constraints
        $result = $type->execute(['not-an-int'], $context);
        self::assertSame(Value::INVALID, $result);
    }

    public function testConstraintsWorkWithIterables(): void
    {
        $type = (new ListType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator(['a', 'b']), $context);

        self::assertSame(['a', 'b'], $result);
        self::assertSame([], $context->issues);
    }

    public function testConstraintsRejectEmptyIterables(): void
    {
        $type = (new ListType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator([]), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too few items.', $context->issues[0]->message);
    }

    public function testConstraintsWorkWithGenerators(): void
    {
        $type = (new ListType(new StringType()))->minItems(2);
        $context = new ValidationContext();

        $generator = (function () {
            yield 'x';
            yield 'y';
            yield 'z';
        })();

        $result = $type->execute($generator, $context);

        self::assertSame(['x', 'y', 'z'], $result);
        self::assertSame([], $context->issues);
    }
}
