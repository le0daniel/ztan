<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Complex;

use Le0daniel\Assertions\Data\ParseError;
use Le0daniel\Assertions\Data\ParseSuccess;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\CatchType;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\TupleType;
use Le0daniel\Assertions\Types\Scalars\IntType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;

final class TupleTypeTest extends TestCase
{
    public function testValidTuple(): void
    {
        $type = new TupleType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute(['hello', 42], $context);

        self::assertSame(['hello', 42], $result);
        self::assertSame([], $context->issues);
    }

    public function testTooFewElements(): void
    {
        $type = new TupleType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute(['hello'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected exactly 2 elements, got 1.', $context->issues[0]->message);
    }

    public function testTooManyElements(): void
    {
        $type = new TupleType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute(['hello', 42, 'extra'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected exactly 2 elements, got 3.', $context->issues[0]->message);
    }

    public function testNonArrayRejected(): void
    {
        $type = new TupleType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute('not-an-array', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected a tuple.', $context->issues[0]->message);
    }

    public function testNonListArrayRejected(): void
    {
        $type = new TupleType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute(['a' => 'hello', 'b' => 42], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected a tuple, got a non-sequential array.', $context->issues[0]->message);
    }

    public function testInvalidElementAtPosition(): void
    {
        $type = new TupleType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute(['hello', 'not-an-int'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('1', $context->issues[0]->getPathAsString());
    }

    public function testEmptyTupleAcceptsEmptyArray(): void
    {
        $type = new TupleType();
        $context = new ValidationContext();

        $result = $type->execute([], $context);

        self::assertSame([], $result);
        self::assertSame([], $context->issues);
    }

    public function testEmptyTupleRejectsNonEmpty(): void
    {
        $type = new TupleType();
        $context = new ValidationContext();

        $result = $type->execute(['extra'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected exactly 0 elements, got 1.', $context->issues[0]->message);
    }

    public function testNestedArrayShapePathTracking(): void
    {
        $type = new TupleType(
            new ArrayShapeType(['name' => new StringType()]),
            new IntType(),
        );
        $context = new ValidationContext();

        $result = $type->execute([['name' => 123], 42], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('0.name', $context->issues[0]->getPathAsString());
    }

    public function testSafeParseSuccess(): void
    {
        $type = new TupleType(new StringType(), new IntType());

        $result = $type->safeParse(['hello', 42]);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertSame(['hello', 42], $result->data);
        self::assertFalse($result->isPartial());
    }

    public function testSafeParseError(): void
    {
        $type = new TupleType(new StringType(), new IntType());

        $result = $type->safeParse('not-an-array');

        self::assertInstanceOf(ParseError::class, $result);
        self::assertNotEmpty($result->issues);
    }

    public function testCompositionWithCatchType(): void
    {
        $type = new TupleType(
            new CatchType(new StringType(), 'fallback'),
            new IntType(),
        );
        $context = new ValidationContext();

        $result = $type->execute([123, 42], $context);

        self::assertSame(['fallback', 42], $result);
        self::assertNotEmpty($context->issues);
    }
}
