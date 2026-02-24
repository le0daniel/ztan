<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Complex;

use Le0daniel\Ztan\Data\ParseSuccess;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\CatchType;
use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\Scalars\StringType;
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
        self::assertCount(1, $context->issues);
        self::assertSame('Value is not an array.', $context->issues[0]->message);
    }

    public function testMissingRequiredPropertyErrorAtPath(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute([], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Property name is required.', $context->issues[0]->message);
        self::assertSame('name', $context->issues[0]->getPathAsString());
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
        self::assertCount(1, $context->issues);
        self::assertSame('user.name', $context->issues[0]->getPathAsString());
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
        self::assertCount(1, $context->issues);
        self::assertSame('user', $context->issues[0]->getPathAsString());
        self::assertSame('Value is not an array.', $context->issues[0]->message);
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
        self::assertCount(1, $context->issues);
        self::assertSame('tags.a', $context->issues[0]->getPathAsString());
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
        self::assertCount(2, $context->issues);
        self::assertSame('first', $context->issues[0]->getPathAsString());
        self::assertSame('second', $context->issues[1]->getPathAsString());
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

    public function testExecutePropertyReturnsValidatedValue(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);

        $result = $type->executeProperty('name', ['name' => 'Alice'], new ValidationContext());

        self::assertSame('Alice', $result);
    }

    public function testExecutePropertyReturnsInvalidForWrongValueType(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);

        $result = $type->executeProperty('name', ['name' => 123], new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyReturnsInvalidForMissingProperty(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);

        $result = $type->executeProperty('name', ['age' => 30], new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyReturnsInvalidForNonArray(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);

        $result = $type->executeProperty('name', 'not-an-array', new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyReturnsInvalidForUnknownProperty(): void
    {
        $type = new ArrayShapeType([
            'name' => new StringType(),
        ]);

        $result = $type->executeProperty('age', ['age' => 30], new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyWorksWithOptionalSuffix(): void
    {
        $type = new ArrayShapeType([
            'age?' => new StringType(),
        ]);

        $result = $type->executeProperty('age', ['age' => 'thirty'], new ValidationContext());

        self::assertSame('thirty', $result);
    }
}
