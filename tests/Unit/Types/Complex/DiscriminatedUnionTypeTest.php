<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Complex;

use Le0daniel\Ztan\Data\ParseError;
use Le0daniel\Ztan\Data\ParseSuccess;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\DiscriminatedUnionType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\LiteralType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;

final class DiscriminatedUnionTypeTest extends TestCase
{
    public function testMatchesFirstShape(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
            new ArrayShapeType(['type' => new LiteralType('b'), 'age' => new IntType()]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['type' => 'a', 'name' => 'Alice'], $context);

        self::assertSame(['type' => 'a', 'name' => 'Alice'], $result);
        self::assertSame([], $context->issues);
    }

    public function testMatchesSecondShape(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
            new ArrayShapeType(['type' => new LiteralType('b'), 'age' => new IntType()]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['type' => 'b', 'age' => 42], $context);

        self::assertSame(['type' => 'b', 'age' => 42], $result);
        self::assertSame([], $context->issues);
    }

    public function testMatchesThirdShape(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
            new ArrayShapeType(['type' => new LiteralType('b'), 'age' => new IntType()]),
            new ArrayShapeType(['type' => new LiteralType('c'), 'score' => new IntType()]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['type' => 'c', 'score' => 100], $context);

        self::assertSame(['type' => 'c', 'score' => 100], $result);
        self::assertSame([], $context->issues);
    }

    public function testMissingDiscriminatorFails(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['name' => 'Alice'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value does not match any type in the discriminated union.', $context->issues[0]->message);
    }

    public function testNoMatchingDiscriminatorFails(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
            new ArrayShapeType(['type' => new LiteralType('b'), 'age' => new IntType()]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['type' => 'c', 'data' => 'x'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value does not match any type in the discriminated union.', $context->issues[0]->message);
    }

    public function testNonArrayInputFails(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
        ]);
        $context = new ValidationContext();

        $result = $type->execute('not-an-array', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertNotEmpty($context->issues);
    }

    public function testMatchedShapeWithInvalidOtherProperties(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
        ]);
        $context = new ValidationContext();

        // Discriminator matches but 'name' is wrong type
        $result = $type->execute(['type' => 'a', 'name' => 123], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertNotEmpty($context->issues);
    }

    public function testSafeParseSuccess(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
            new ArrayShapeType(['type' => new LiteralType('b'), 'age' => new IntType()]),
        ]);

        $result = $type->safeParse(['type' => 'a', 'name' => 'Alice']);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertSame(['type' => 'a', 'name' => 'Alice'], $result->data);
        self::assertFalse($result->isPartial());
    }

    public function testSafeParseError(): void
    {
        $type = new DiscriminatedUnionType('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
        ]);

        $result = $type->safeParse(['type' => 'unknown']);

        self::assertInstanceOf(ParseError::class, $result);
        self::assertNotEmpty($result->issues);
    }
}
