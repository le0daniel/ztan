<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Complex;

use Le0daniel\Ztan\Data\ParseSuccess;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\CatchType;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use PHPUnit\Framework\TestCase;

final class ObjectShapeTypeTest extends TestCase
{
    public function testValidShapePasses(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $input = new \stdClass();
        $input->name = 'Alice';

        $result = $type->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame([], $context->issues);
    }

    public function testNonObjectRejected(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute('not-an-object', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is not an object.', $context->issues[0]->message);
    }

    public function testArrayRejected(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(['name' => 'Alice'], $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Value is not an object.', $context->issues[0]->message);
    }

    public function testMissingRequiredPropertyErrorAtPath(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $result = $type->execute(new \stdClass(), $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Property name is required.', $context->issues[0]->message);
        self::assertSame('name', $context->issues[0]->getPathAsString());
    }

    public function testExtraPropertiesDropped(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $input = new \stdClass();
        $input->name = 'Alice';
        $input->extra = 'value';

        $result = $type->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertFalse(property_exists($result, 'extra'));
        self::assertSame([], $context->issues);
    }

    public function testMissingOptionalOmittedFromOutput(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
            'age?' => new StringType(),
        ]);
        $context = new ValidationContext();

        $input = new \stdClass();
        $input->name = 'Alice';

        $result = $type->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertFalse(property_exists($result, 'age'));
        self::assertSame([], $context->issues);
    }

    public function testPresentOptionalIncluded(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
            'age?' => new StringType(),
        ]);
        $context = new ValidationContext();

        $input = new \stdClass();
        $input->name = 'Alice';
        $input->age = '30';

        $result = $type->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame('30', $result->age);
        self::assertSame([], $context->issues);
    }

    public function testNestedObjectShapeValidPasses(): void
    {
        $type = new ObjectShapeType([
            'user' => new ObjectShapeType([
                'name' => new StringType(),
            ]),
        ]);
        $context = new ValidationContext();

        $inner = new \stdClass();
        $inner->name = 'Alice';
        $input = new \stdClass();
        $input->user = $inner;

        $result = $type->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertInstanceOf(\stdClass::class, $result->user);
        self::assertSame('Alice', $result->user->name);
        self::assertSame([], $context->issues);
    }

    public function testNestedShapeInvalidValueIssueAtPath(): void
    {
        $type = new ObjectShapeType([
            'user' => new ObjectShapeType([
                'name' => new StringType(),
            ]),
        ]);
        $context = new ValidationContext();

        $inner = new \stdClass();
        $inner->name = 123;
        $input = new \stdClass();
        $input->user = $inner;

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('user.name', $context->issues[0]->getPathAsString());
    }

    public function testNullPropertyExistsAndIsValidated(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $input = new \stdClass();
        $input->name = null;

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('name', $context->issues[0]->getPathAsString());
    }

    public function testMagicIssetAndGetSupport(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);
        $context = new ValidationContext();

        $input = new class {
            public function __isset(string $name): bool
            {
                return $name === 'name';
            }

            public function __get(string $name): mixed
            {
                if ($name === 'name') {
                    return 'Alice';
                }
                return null;
            }
        };

        $result = $type->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('Alice', $result->name);
        self::assertSame([], $context->issues);
    }

    public function testCatchTypeComposition(): void
    {
        $type = new ObjectShapeType([
            'name' => new CatchType(new StringType(), null),
        ]);
        $context = new ValidationContext();

        $input = new \stdClass();
        $input->name = 123;

        $result = $type->execute($input, $context);

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertNull($result->name);
    }

    public function testMultipleErrorsAtBothPaths(): void
    {
        $type = new ObjectShapeType([
            'first' => new StringType(),
            'second' => new StringType(),
        ]);
        $context = new ValidationContext();

        $input = new \stdClass();
        $input->first = 1;
        $input->second = 2;

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(2, $context->issues);
        self::assertSame('first', $context->issues[0]->getPathAsString());
        self::assertSame('second', $context->issues[1]->getPathAsString());
    }

    public function testSafeParseIsPartialWhenCatchBarrierReached(): void
    {
        $type = new ObjectShapeType([
            'name' => new CatchType(new StringType(), null),
        ]);

        $input = new \stdClass();
        $input->name = 123;

        $result = $type->safeParse($input);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertTrue($result->isPartial());
        self::assertInstanceOf(\stdClass::class, $result->data);
        self::assertNull($result->data->name);
        self::assertNotEmpty($result->issues);
    }

    public function testSafeParseIsNotPartialWhenFullyValid(): void
    {
        $type = new ObjectShapeType([
            'name' => new CatchType(new StringType(), null),
        ]);

        $input = new \stdClass();
        $input->name = 'Alice';

        $result = $type->safeParse($input);

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertFalse($result->isPartial());
        self::assertInstanceOf(\stdClass::class, $result->data);
        self::assertSame('Alice', $result->data->name);
        self::assertSame([], $result->issues);
    }

    public function testExecutePropertyReturnsValidatedValue(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);

        $input = new \stdClass();
        $input->name = 'Alice';

        $result = $type->executeProperty('name', $input, new ValidationContext());

        self::assertSame('Alice', $result);
    }

    public function testExecutePropertyReturnsInvalidForWrongValueType(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);

        $input = new \stdClass();
        $input->name = 123;

        $result = $type->executeProperty('name', $input, new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyReturnsInvalidForMissingProperty(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);

        $input = new \stdClass();
        $input->age = 30;

        $result = $type->executeProperty('name', $input, new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyReturnsInvalidForNonObject(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);

        $result = $type->executeProperty('name', 'not-an-object', new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyReturnsInvalidForUnknownProperty(): void
    {
        $type = new ObjectShapeType([
            'name' => new StringType(),
        ]);

        $input = new \stdClass();
        $input->age = 30;

        $result = $type->executeProperty('age', $input, new ValidationContext());

        self::assertSame(Value::INVALID, $result);
    }

    public function testExecutePropertyWorksWithOptionalSuffix(): void
    {
        $type = new ObjectShapeType([
            'age?' => new StringType(),
        ]);

        $input = new \stdClass();
        $input->age = 'thirty';

        $result = $type->executeProperty('age', $input, new ValidationContext());

        self::assertSame('thirty', $result);
    }
}
