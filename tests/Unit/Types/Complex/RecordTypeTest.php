<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Complex;

use ArrayIterator;
use IteratorAggregate;
use Le0daniel\Ztan\Data\ParseSuccess;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\CatchType;
use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;
use stdClass;
use Traversable;

final class RecordTypeTest extends TestCase
{
    public function testValidRecordPasses(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(['a' => 'one', 'b' => 'two'], $context);

        self::assertSame(['a' => 'one', 'b' => 'two'], $result);
        self::assertSame([], $context->issues);
    }

    public function testEmptyArrayPasses(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute([], $context);

        self::assertSame([], $result);
        self::assertSame([], $context->issues);
    }

    public function testNonArrayRejected(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute('not-an-array', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected an iterable record.', $context->issues[0]->message);
    }

    public function testInvalidValueRejected(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(['a' => 123], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('a', $context->issues[0]->getPathAsString());
    }

    public function testNonStringKeyRejected(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute([0 => 'val'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Record key must be a string.', $context->issues[0]->message);
    }

    public function testNestedRecordOfRecordsValid(): void
    {
        $type = new RecordType(new RecordType(new StringType()));
        $context = new ValidationContext();

        $result = $type->execute([
            'group1' => ['a' => 'one'],
            'group2' => ['b' => 'two'],
        ], $context);

        self::assertSame([
            'group1' => ['a' => 'one'],
            'group2' => ['b' => 'two'],
        ], $result);
        self::assertSame([], $context->issues);
    }

    public function testNestedRecordOfRecordsInvalid(): void
    {
        $type = new RecordType(new RecordType(new StringType()));
        $context = new ValidationContext();

        $result = $type->execute([
            'group1' => ['a' => 123],
        ], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('group1.a', $context->issues[0]->getPathAsString());
    }

    public function testShapeInsideRecordValid(): void
    {
        $type = new RecordType(new ArrayShapeType([
            'name' => new StringType(),
        ]));
        $context = new ValidationContext();

        $result = $type->execute([
            'user1' => ['name' => 'Alice'],
            'user2' => ['name' => 'Bob'],
        ], $context);

        self::assertSame([
            'user1' => ['name' => 'Alice'],
            'user2' => ['name' => 'Bob'],
        ], $result);
        self::assertSame([], $context->issues);
    }

    public function testShapeInsideRecordInvalid(): void
    {
        $type = new RecordType(new ArrayShapeType([
            'name' => new StringType(),
        ]));
        $context = new ValidationContext();

        $result = $type->execute([
            'user1' => ['name' => 123],
        ], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('user1.name', $context->issues[0]->getPathAsString());
    }

    public function testSafeParseIsPartialWhenCatchBarrierReached(): void
    {
        $type = new RecordType(new CatchType(new StringType(), null));

        $result = $type->safeParse(['a' => 'valid', 'b' => 123]);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertTrue($result->isPartial());
        self::assertSame(['a' => 'valid', 'b' => null], $result->data);
        self::assertNotEmpty($result->issues);
    }

    public function testArrayIteratorWithStringKeys(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator(['a' => 'one', 'b' => 'two']), $context);

        self::assertSame(['a' => 'one', 'b' => 'two'], $result);
        self::assertSame([], $context->issues);
    }

    public function testGeneratorWithStringKeyYields(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $generator = (function () {
            yield 'x' => 'one';
            yield 'y' => 'two';
        })();

        $result = $type->execute($generator, $context);

        self::assertSame(['x' => 'one', 'y' => 'two'], $result);
        self::assertSame([], $context->issues);
    }

    public function testEmptyArrayIterator(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator([]), $context);

        self::assertSame([], $result);
        self::assertSame([], $context->issues);
    }

    public function testIteratorWithIntKeysRejected(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator([0 => 'a', 1 => 'b']), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(2, $context->issues);
        self::assertSame('Record key must be a string.', $context->issues[0]->message);
        self::assertSame('Record key must be a string.', $context->issues[1]->message);
    }

    public function testIteratorWithInvalidValues(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator(['a' => 123, 'b' => 456]), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(2, $context->issues);
        self::assertSame('a', $context->issues[0]->getPathAsString());
        self::assertSame('b', $context->issues[1]->getPathAsString());
    }

    public function testIteratorAggregateObject(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $iterable = new class implements IteratorAggregate {
            public function getIterator(): Traversable
            {
                return new ArrayIterator(['key' => 'value']);
            }
        };

        $result = $type->execute($iterable, $context);

        self::assertSame(['key' => 'value'], $result);
        self::assertSame([], $context->issues);
    }

    public function testNonIterableObjectRejected(): void
    {
        $type = new RecordType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(new stdClass(), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected an iterable record.', $context->issues[0]->message);
    }

    public function testNonEmptyRejectsEmptyRecord(): void
    {
        $type = (new RecordType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute([], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too few properties.', $context->issues[0]->message);
    }

    public function testNonEmptyAcceptsSingleProperty(): void
    {
        $type = (new RecordType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute(['a' => 'b'], $context);

        self::assertSame(['a' => 'b'], $result);
        self::assertSame([], $context->issues);
    }

    public function testMinPropertiesBoundary(): void
    {
        $type = (new RecordType(new StringType()))->minProperties(2);
        $context = new ValidationContext();

        // 1 property should fail
        $result = $type->execute(['a' => 'one'], $context);
        self::assertSame(Value::INVALID, $result);

        // 2 properties should pass
        $context2 = new ValidationContext();
        $result2 = $type->execute(['a' => 'one', 'b' => 'two'], $context2);
        self::assertSame(['a' => 'one', 'b' => 'two'], $result2);
    }

    public function testMaxPropertiesBoundary(): void
    {
        $type = (new RecordType(new StringType()))->maxProperties(3);
        $context = new ValidationContext();

        // 4 properties should fail
        $result = $type->execute(['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4'], $context);
        self::assertSame(Value::INVALID, $result);

        // 3 properties should pass
        $context2 = new ValidationContext();
        $result2 = $type->execute(['a' => '1', 'b' => '2', 'c' => '3'], $context2);
        self::assertSame(['a' => '1', 'b' => '2', 'c' => '3'], $result2);
    }

    public function testConstraintsComposeWithValueValidation(): void
    {
        $type = (new RecordType(new IntType()))->minProperties(1);
        $context = new ValidationContext();

        // Invalid values should fail before constraints
        $result = $type->execute(['a' => 'not-an-int'], $context);
        self::assertSame(Value::INVALID, $result);
    }

    public function testConstraintsWorkWithIterables(): void
    {
        $type = (new RecordType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator(['a' => 'one']), $context);

        self::assertSame(['a' => 'one'], $result);
        self::assertSame([], $context->issues);
    }

    public function testConstraintsRejectEmptyIterables(): void
    {
        $type = (new RecordType(new StringType()))->nonEmpty();
        $context = new ValidationContext();

        $result = $type->execute(new ArrayIterator([]), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too few properties.', $context->issues[0]->message);
    }
}
