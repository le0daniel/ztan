<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Complex;

use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;

final class ObjectShapeTypeExtendOmitTest extends TestCase
{
    private ObjectShapeType $base;

    protected function setUp(): void
    {
        $this->base = new ObjectShapeType([
            'name' => new StringType(),
            'age'  => new IntType(),
        ]);
    }

    private static function obj(mixed ...$props): \stdClass
    {
        $o = new \stdClass();
        foreach ($props as $key => $value) {
            $o->{$key} = $value;
        }
        return $o;
    }

    public function testExtendAddsNewField(): void
    {
        $extended = $this->base->extend(['email' => new StringType()]);
        $context  = new ValidationContext();

        $input = self::obj(name: 'Alice', age: 30, email: 'a@b.com');
        $result = $extended->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame(30, $result->age);
        self::assertSame('a@b.com', $result->email);
        self::assertSame([], $context->issues);
    }

    public function testExtendOverridesExistingField(): void
    {
        $extended = $this->base->extend(['age' => new StringType()]);
        $context  = new ValidationContext();

        $input = self::obj(name: 'Alice', age: 'thirty');
        $result = $extended->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame('thirty', $result->age);
        self::assertSame([], $context->issues);
    }

    public function testExtendWithOptionalFieldWorks(): void
    {
        $extended = $this->base->extend(['role?' => new StringType()]);

        // without optional
        $context = new ValidationContext();
        $input   = self::obj(name: 'Alice', age: 30);
        $result  = $extended->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertFalse(property_exists($result, 'role'));
        self::assertSame([], $context->issues);

        // with optional present
        $context2 = new ValidationContext();
        $input2   = self::obj(name: 'Alice', age: 30, role: 'admin');
        $result2  = $extended->execute($input2, $context2);

        self::assertInstanceOf(\stdClass::class, $result2);
        self::assertSame('admin', $result2->role);
        self::assertSame([], $context2->issues);
    }

    public function testExtendDoesNotMutateOriginal(): void
    {
        $this->base->extend(['email' => new StringType()]);

        $context = new ValidationContext();
        $input   = self::obj(name: 'Alice', age: 30);
        $result  = $this->base->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertFalse(property_exists($result, 'email'));
        self::assertSame([], $context->issues);
    }

    public function testOmitRemovesRequiredField(): void
    {
        $omitted = $this->base->omit(['age']);
        $context = new ValidationContext();

        $input  = self::obj(name: 'Alice');
        $result = $omitted->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertFalse(property_exists($result, 'age'));
        self::assertSame([], $context->issues);
    }

    public function testOmitRemovesOptionalField(): void
    {
        $base    = new ObjectShapeType(['name' => new StringType(), 'age?' => new IntType()]);
        $omitted = $base->omit(['age']);
        $context = new ValidationContext();

        $input  = self::obj(name: 'Alice');
        $result = $omitted->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertFalse(property_exists($result, 'age'));
        self::assertSame([], $context->issues);
    }

    public function testOmitUnknownKeyIsNoOp(): void
    {
        $omitted = $this->base->omit(['unknown']);
        $context = new ValidationContext();

        $input  = self::obj(name: 'Alice', age: 30);
        $result = $omitted->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame(30, $result->age);
        self::assertSame([], $context->issues);
    }

    public function testOmitDoesNotMutateOriginal(): void
    {
        $this->base->omit(['age']);

        $context = new ValidationContext();
        $input   = self::obj(name: 'Alice', age: 30);
        $result  = $this->base->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame(30, $result->age);
        self::assertSame([], $context->issues);
    }

    public function testExtendThenOmit(): void
    {
        $shape   = $this->base->extend(['email' => new StringType()])->omit(['age']);
        $context = new ValidationContext();

        $input  = self::obj(name: 'Alice', email: 'a@b.com');
        $result = $shape->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame('a@b.com', $result->email);
        self::assertFalse(property_exists($result, 'age'));
        self::assertSame([], $context->issues);
    }

    public function testOmitedRequiredFieldCausesValidationError(): void
    {
        $omitted = $this->base->omit(['age']);
        $context = new ValidationContext();

        $result = $omitted->execute(new \stdClass(), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Property name is required.', $context->issues[0]->message);
    }
}
