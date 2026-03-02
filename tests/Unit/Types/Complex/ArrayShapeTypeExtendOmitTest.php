<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Complex;

use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;

final class ArrayShapeTypeExtendOmitTest extends TestCase
{
    private ArrayShapeType $base;

    protected function setUp(): void
    {
        $this->base = new ArrayShapeType([
            'name' => new StringType(),
            'age'  => new IntType(),
        ]);
    }

    public function testExtendAddsNewField(): void
    {
        $extended = $this->base->extend(['email' => new StringType()]);
        $context  = new ValidationContext();

        $result = $extended->execute(['name' => 'Alice', 'age' => 30, 'email' => 'a@b.com'], $context);

        self::assertSame(['name' => 'Alice', 'age' => 30, 'email' => 'a@b.com'], $result);
        self::assertSame([], $context->issues);
    }

    public function testExtendOverridesExistingField(): void
    {
        $extended = $this->base->extend(['age' => new StringType()]);
        $context  = new ValidationContext();

        $result = $extended->execute(['name' => 'Alice', 'age' => 'thirty'], $context);

        self::assertSame(['name' => 'Alice', 'age' => 'thirty'], $result);
        self::assertSame([], $context->issues);
    }

    public function testExtendWithOptionalFieldWorks(): void
    {
        $extended = $this->base->extend(['role?' => new StringType()]);
        $context  = new ValidationContext();

        // without optional
        $result = $extended->execute(['name' => 'Alice', 'age' => 30], $context);

        self::assertSame(['name' => 'Alice', 'age' => 30], $result);
        self::assertSame([], $context->issues);

        // with optional
        $context2 = new ValidationContext();
        $result2  = $extended->execute(['name' => 'Alice', 'age' => 30, 'role' => 'admin'], $context2);

        self::assertSame(['name' => 'Alice', 'age' => 30, 'role' => 'admin'], $result2);
        self::assertSame([], $context2->issues);
    }

    public function testExtendDoesNotMutateOriginal(): void
    {
        $this->base->extend(['email' => new StringType()]);

        $context = new ValidationContext();
        $result  = $this->base->execute(['name' => 'Alice', 'age' => 30], $context);

        self::assertSame(['name' => 'Alice', 'age' => 30], $result);
        self::assertSame([], $context->issues);
    }

    public function testOmitRemovesRequiredField(): void
    {
        $omitted = $this->base->omit(['age']);
        $context = new ValidationContext();

        $result = $omitted->execute(['name' => 'Alice'], $context);

        self::assertSame(['name' => 'Alice'], $result);
        self::assertSame([], $context->issues);
    }

    public function testOmitRemovesOptionalField(): void
    {
        $base    = new ArrayShapeType(['name' => new StringType(), 'age?' => new IntType()]);
        $omitted = $base->omit(['age']);
        $context = new ValidationContext();

        $result = $omitted->execute(['name' => 'Alice'], $context);

        self::assertSame(['name' => 'Alice'], $result);
        self::assertSame([], $context->issues);
    }

    public function testOmitUnknownKeyIsNoOp(): void
    {
        $omitted = $this->base->omit(['unknown']);
        $context = new ValidationContext();

        $result = $omitted->execute(['name' => 'Alice', 'age' => 30], $context);

        self::assertSame(['name' => 'Alice', 'age' => 30], $result);
        self::assertSame([], $context->issues);
    }

    public function testOmitDoesNotMutateOriginal(): void
    {
        $this->base->omit(['age']);

        $context = new ValidationContext();
        $result  = $this->base->execute(['name' => 'Alice', 'age' => 30], $context);

        self::assertSame(['name' => 'Alice', 'age' => 30], $result);
        self::assertSame([], $context->issues);
    }

    public function testOmitRequiredFieldMakesItMissing(): void
    {
        $omitted = $this->base->omit(['age']);
        $context = new ValidationContext();

        $result = $omitted->execute(['name' => 'Alice', 'age' => 30], $context);

        self::assertSame(['name' => 'Alice'], $result);
    }

    public function testExtendThenOmit(): void
    {
        $result = $this->base->extend(['email' => new StringType()])->omit(['age']);
        $context = new ValidationContext();

        $output = $result->execute(['name' => 'Alice', 'email' => 'a@b.com'], $context);

        self::assertSame(['name' => 'Alice', 'email' => 'a@b.com'], $output);
        self::assertSame([], $context->issues);
    }

    public function testOmitedRequiredFieldCausesValidationError(): void
    {
        $omitted = $this->base->omit(['age']);
        $context = new ValidationContext();

        $result = $omitted->execute([], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Property name is required.', $context->issues[0]->message);
    }
}
