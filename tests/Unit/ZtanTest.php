<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit;

use Le0daniel\Assertions\CoerceBuilder;
use Le0daniel\Assertions\Data\ValidationException;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\DiscriminatedUnionType;
use Le0daniel\Assertions\Types\Complex\ListType;
use Le0daniel\Assertions\Types\Complex\ObjectShapeType;
use Le0daniel\Assertions\Types\Complex\RecordType;
use Le0daniel\Assertions\Types\Complex\TupleType;
use Le0daniel\Assertions\Types\Complex\UnionType;
use Le0daniel\Assertions\Types\Scalars\BoolType;
use Le0daniel\Assertions\Types\Scalars\DateTimeStringType;
use Le0daniel\Assertions\Types\Scalars\EnumType;
use Le0daniel\Assertions\Types\Scalars\FloatType;
use Le0daniel\Assertions\Types\Scalars\InstanceType;
use Le0daniel\Assertions\Types\Scalars\IntType;
use Le0daniel\Assertions\Types\Scalars\LiteralType;
use Le0daniel\Assertions\Types\Scalars\MixedType;
use Le0daniel\Assertions\Types\Scalars\NeverType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use Le0daniel\Assertions\Ztan;
use PHPUnit\Framework\TestCase;

enum ZtanTestStatus {
    case ACTIVE;
    case INACTIVE;
}

enum ZtanTestBackedStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

final class ZtanTest extends TestCase
{
    public function testStringReturnsStringType(): void
    {
        self::assertInstanceOf(StringType::class, Ztan::string());
    }

    public function testIntReturnsIntType(): void
    {
        self::assertInstanceOf(IntType::class, Ztan::int());
    }

    public function testFloatReturnsFloatType(): void
    {
        self::assertInstanceOf(FloatType::class, Ztan::float());
    }

    public function testBoolReturnsBoolType(): void
    {
        self::assertInstanceOf(BoolType::class, Ztan::bool());
    }

    public function testMixedReturnsMixedType(): void
    {
        self::assertInstanceOf(MixedType::class, Ztan::mixed());
    }

    public function testNeverReturnsNeverType(): void
    {
        self::assertInstanceOf(NeverType::class, Ztan::never());
    }

    public function testDateTimeStringReturnsDateTimeStringType(): void
    {
        self::assertInstanceOf(DateTimeStringType::class, Ztan::dateTimeString('Y-m-d'));
    }

    public function testLiteralReturnsLiteralType(): void
    {
        self::assertInstanceOf(LiteralType::class, Ztan::literal('hello'));
    }

    public function testEnumReturnsEnumType(): void
    {
        self::assertInstanceOf(EnumType::class, Ztan::enum(ZtanTestStatus::class));
    }

    public function testInstanceReturnsInstanceType(): void
    {
        self::assertInstanceOf(InstanceType::class, Ztan::instance(\DateTimeImmutable::class));
    }

    public function testListReturnsListType(): void
    {
        self::assertInstanceOf(ListType::class, Ztan::list(new StringType()));
    }

    public function testRecordReturnsRecordType(): void
    {
        self::assertInstanceOf(RecordType::class, Ztan::record(new StringType()));
    }

    public function testArrayShapeReturnsArrayShapeType(): void
    {
        self::assertInstanceOf(ArrayShapeType::class, Ztan::arrayShape(['name' => new StringType()]));
    }

    public function testObjectShapeReturnsObjectShapeType(): void
    {
        self::assertInstanceOf(ObjectShapeType::class, Ztan::objectShape(['name' => new StringType()]));
    }

    public function testUnionReturnsUnionType(): void
    {
        self::assertInstanceOf(UnionType::class, Ztan::union(new StringType(), new IntType()));
    }

    public function testTupleReturnsTupleType(): void
    {
        self::assertInstanceOf(TupleType::class, Ztan::tuple(new StringType(), new IntType()));
    }

    public function testDiscriminatedUnionReturnsDiscriminatedUnionType(): void
    {
        self::assertInstanceOf(DiscriminatedUnionType::class, Ztan::discriminatedUnion('type', [
            new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
        ]));
    }

    public function testCoerceReturnsCoerceBuilder(): void
    {
        self::assertInstanceOf(CoerceBuilder::class, Ztan::coerce());
    }

    public function testParseWorksThroughFacade(): void
    {
        self::assertSame('hello', Ztan::string()->parse('hello'));
        self::assertSame(42, Ztan::int()->parse(42));
        self::assertSame(3.14, Ztan::float()->parse(3.14));
        self::assertSame(true, Ztan::bool()->parse(true));
        self::assertSame('hello', Ztan::literal('hello')->parse('hello'));
        self::assertSame(ZtanTestStatus::ACTIVE, Ztan::enum(ZtanTestStatus::class)->parse(ZtanTestStatus::ACTIVE));
    }

    public function testArrayShapeParseWorksThroughFacade(): void
    {
        $result = Ztan::arrayShape([
            'name' => new StringType(),
            'age' => new IntType(),
        ])->parse(['name' => 'John', 'age' => 30]);

        self::assertSame(['name' => 'John', 'age' => 30], $result);
    }

    public function testCoerceBuilderActuallyCoerces(): void
    {
        self::assertSame('42', Ztan::coerce()->string()->parse(42));
        self::assertSame(42, Ztan::coerce()->int()->parse('42'));
        self::assertSame(3.14, Ztan::coerce()->float()->parse('3.14'));
        self::assertSame(true, Ztan::coerce()->bool()->parse(1));
        self::assertSame(ZtanTestBackedStatus::ACTIVE, Ztan::coerce()->enum(ZtanTestBackedStatus::class)->parse('active'));
    }

    public function testDirectTypesDoNotCoerce(): void
    {
        $this->expectException(ValidationException::class);
        Ztan::string()->parse(42);
    }

    public function testDirectIntDoesNotCoerce(): void
    {
        $this->expectException(ValidationException::class);
        Ztan::int()->parse('42');
    }

    public function testMethodChainingFromFacade(): void
    {
        self::assertSame('hello', Ztan::string()->trim()->parse('  hello  '));
    }
}
