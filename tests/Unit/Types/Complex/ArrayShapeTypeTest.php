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

final class ArrayShapeTypeTest extends TestCase
{
    public function testValidShapePasses(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['name' => 'Alice'], $context);

        self::assertSame(['name' => 'Alice'], $result);
        self::assertSame([], $context->issues);
    }

    public function testNonArrayRejected(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute('not-an-array', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues[''] ?? []);
        self::assertSame('Value is not an array.', $context->issues[''][0]->message);
    }

    public function testMissingRequiredPropertyErrorAtPath(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute([], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues['name'] ?? []);
        self::assertSame('Property name does not exist}', $context->issues['name'][0]->message);
    }

    public function testExtraKeysDropped(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['name' => 'Alice', 'extra' => 'value'], $context);

        self::assertSame(['name' => 'Alice'], $result);
        self::assertSame([], $context->issues);
    }

    public function testMissingOptionalOmittedFromOutput(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
            'age?' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['name' => 'Alice'], $context);

        self::assertSame(['name' => 'Alice'], $result);
        self::assertSame([], $context->issues);
    }

    public function testPresentOptionalIncluded(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
            'age?' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['name' => 'Alice', 'age' => '30'], $context);

        self::assertSame(['name' => 'Alice', 'age' => '30'], $result);
        self::assertSame([], $context->issues);
    }

    public function testNestedShapeValidPasses(): void
    {
        $type = new ArrayShapeType([
            'user' => new ArrayShapeType([
                'name' => new StringType(),
            ]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['user' => ['name' => 'Alice']], $context);

        self::assertSame(['user' => ['name' => 'Alice']], $result);
        self::assertSame([], $context->issues);
    }

    public function testNestedShapeInvalidValueIssueAtPath(): void
    {
        $type = new ArrayShapeType([
            'user' => new ArrayShapeType([
                'name' => new StringType(),
            ]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['user' => ['name' => 123]], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues['user.name'] ?? []);
    }

    public function testNonArrayAtNestedPositionIssueAtPath(): void
    {
        $type = new ArrayShapeType([
            'user' => new ArrayShapeType([
                'name' => new StringType(),
            ]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['user' => 'not-an-array'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues['user'] ?? []);
        self::assertSame('Value is not an array.', $context->issues['user'][0]->message);
    }

    public function testNestedRecordTypeInShapePasses(): void
    {
        $type = new ArrayShapeType([
            'tags' => new RecordType(new StringType()),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['tags' => ['a' => 'one', 'b' => 'two']], $context);

        self::assertSame(['tags' => ['a' => 'one', 'b' => 'two']], $result);
        self::assertSame([], $context->issues);
    }

    public function testNestedRecordTypeInShapeInvalidValueRejected(): void
    {
        $type = new ArrayShapeType([
            'tags' => new RecordType(new StringType()),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['tags' => ['a' => 123]], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues['tags.a'] ?? []);
    }

    public function testCatchTypeComposition(): void
    {
        $type = new ArrayShapeType([
            'name' => new CatchType(new StringType(), null),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['name' => 123], $context);

        self::assertSame(['name' => null], $result);
    }

    public function testMultipleErrorsAtBothPaths(): void
    {
        $type = new ArrayShapeType([
            'first' => new StringType(),
            'second' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['first' => 1, 'second' => 2], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues['first'] ?? []);
        self::assertCount(1, $context->issues['second'] ?? []);
    }

    public function testSafeParseIsPartialWhenCatchBarrierReached(): void
    {
        $type = new ArrayShapeType([
            'name' => new CatchType(new StringType(), null),
        ]);

        $result = $type->safeParse(['name' => 123]);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertTrue($result->isPartial());
        self::assertSame(['name' => null], $result->data);
        self::assertNotEmpty($result->issues);
    }

    public function testSafeParseIsNotPartialWhenFullyValid(): void
    {
        $type = new ArrayShapeType([
            'name' => new CatchType(new StringType(), null),
        ]);

        $result = $type->safeParse(['name' => 'Alice']);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertFalse($result->isPartial());
        self::assertSame(['name' => 'Alice'], $result->data);
        self::assertSame([], $result->issues);
    }
}
