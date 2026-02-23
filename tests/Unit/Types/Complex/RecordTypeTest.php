<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Complex;

use Le0daniel\Assertions\Data\ParseSuccess;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\CatchType;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\RecordType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;

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
        self::assertSame('Expected array.', $context->issues[0]->message);
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
}
