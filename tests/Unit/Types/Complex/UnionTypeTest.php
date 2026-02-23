<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Complex;

use Le0daniel\Assertions\Data\ParseError;
use Le0daniel\Assertions\Data\ParseSuccess;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\CatchType;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\UnionType;
use Le0daniel\Assertions\Types\Scalars\IntType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;

final class UnionTypeTest extends TestCase
{
    public function testFirstTypeMatches(): void
    {
        $type = new UnionType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute('hello', $context);

        self::assertSame('hello', $result);
        self::assertSame([], $context->issues);
    }

    public function testSecondTypeMatches(): void
    {
        $type = new UnionType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute(42, $context);

        self::assertSame(42, $result);
        self::assertSame([], $context->issues);
    }

    public function testAllTypesFail(): void
    {
        $type = new UnionType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute(3.14, $context);

        self::assertSame(Value::INVALID, $result);
        // Issues from both failed probes + summary issue
        self::assertCount(3, $context->issues);
        self::assertSame('Value does not match any type in the union.', $context->issues[2]->message);
    }

    public function testSingleTypeMatching(): void
    {
        $type = new UnionType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute('hello', $context);

        self::assertSame('hello', $result);
        self::assertSame([], $context->issues);
    }

    public function testSingleTypeFailing(): void
    {
        $type = new UnionType(new StringType());
        $context = new ValidationContext();

        $result = $type->execute(123, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(2, $context->issues);
        self::assertSame('Value does not match any type in the union.', $context->issues[1]->message);
    }

    public function testCompositionWithCatchType(): void
    {
        $type = new UnionType(
            new CatchType(new StringType(), 'fallback'),
            new IntType(),
        );
        $context = new ValidationContext();

        // CatchType always succeeds, so even a non-string should match the first type
        $result = $type->execute(3.14, $context);

        self::assertSame('fallback', $result);
        // CatchType succeeds but the inner StringType adds an issue — those get merged
        self::assertNotEmpty($context->issues);
    }

    public function testNestedWithArrayShapePreservesPath(): void
    {
        $type = new UnionType(
            new ArrayShapeType(['name' => new StringType()]),
            new IntType(),
        );
        $context = new ValidationContext();

        // Both fail: ArrayShapeType fails on non-array, IntType fails on non-int
        $result = $type->execute(3.14, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(3, $context->issues);
    }

    public function testNestedArrayShapePathTracking(): void
    {
        $type = new UnionType(
            new ArrayShapeType(['name' => new StringType()]),
            new ArrayShapeType(['age' => new IntType()]),
        );
        $context = new ValidationContext();

        // Pass a valid shape for the second type
        $result = $type->execute(['age' => 42], $context);

        // First type fails (missing 'name'), but second type passes
        self::assertSame(['age' => 42], $result);
        self::assertSame([], $context->issues);
    }

    public function testSafeParseSuccess(): void
    {
        $type = new UnionType(new StringType(), new IntType());

        $result = $type->safeParse('hello');

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertSame('hello', $result->data);
        self::assertFalse($result->isPartial());
    }

    public function testSafeParseError(): void
    {
        $type = new UnionType(new StringType(), new IntType());

        $result = $type->safeParse(3.14);

        self::assertInstanceOf(ParseError::class, $result);
        self::assertNotEmpty($result->issues);
    }

    public function testEmptyUnionAlwaysFails(): void
    {
        $type = new UnionType();
        $context = new ValidationContext();

        $result = $type->execute('anything', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value does not match any type in the union.', $context->issues[0]->message);
    }
}
