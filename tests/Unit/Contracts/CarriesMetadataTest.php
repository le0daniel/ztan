<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Contracts;

use Le0daniel\Ztan\Data\ParseError;
use Le0daniel\Ztan\Types\Scalars\MixedType;
use Le0daniel\Ztan\Types\Scalars\NeverType;
use Le0daniel\Ztan\Types\Scalars\NullType;
use Le0daniel\Ztan\Ztan;
use PHPUnit\Framework\TestCase;

final class CarriesMetadataTest extends TestCase
{
    public function testFreshTypesHaveNoMeta(): void
    {
        self::assertNull(Ztan::string()->getMeta());
        self::assertNull(Ztan::arrayShape(['name' => Ztan::string()])->getMeta());
        self::assertNull(new MixedType()->getMeta());
    }

    public function testMetaReturnsNewInstanceAndLeavesOriginalUntouched(): void
    {
        $original = Ztan::string();
        $described = $original->meta(description: 'A description');

        self::assertNotSame($original, $described);
        self::assertNull($original->getMeta());
        self::assertSame('A description', $described->getMeta()?->description);
    }

    public function testAllMetaFieldsAreExposed(): void
    {
        $meta = Ztan::string()
            ->meta(description: 'd', title: 't', examples: ['a', 'b'], deprecated: true)
            ->getMeta();

        self::assertNotNull($meta);
        self::assertSame('d', $meta->description);
        self::assertSame('t', $meta->title);
        self::assertSame(['a', 'b'], $meta->examples);
        self::assertTrue($meta->deprecated);
    }

    public function testRepeatedMetaReplacesTheWholeMeta(): void
    {
        $meta = Ztan::string()
            ->meta(title: 'First title')
            ->meta(description: 'Second call')
            ->getMeta();

        self::assertNotNull($meta);
        self::assertSame('Second call', $meta->description);
        self::assertNull($meta->title);
    }

    public function testPipeMethodsDropMeta(): void
    {
        self::assertNull(Ztan::string()->meta(description: 'd')->minLength(3)->getMeta());
        self::assertNull(Ztan::int()->meta(description: 'd')->gt(0)->getMeta());
        self::assertNull(Ztan::list(Ztan::string())->meta(description: 'd')->minItems(1)->getMeta());
        self::assertNull(Ztan::record(Ztan::string())->meta(description: 'd')->nonEmpty()->getMeta());
    }

    public function testExtendAndOmitDropMeta(): void
    {
        $shape = Ztan::arrayShape(['name' => Ztan::string()])->meta(description: 'A user');

        self::assertNull($shape->extend(['age' => Ztan::int()])->getMeta());
        self::assertNull($shape->omit(['name'])->getMeta());

        $object = Ztan::objectShape(['name' => Ztan::string()])->meta(description: 'A user');

        self::assertNull($object->extend(['age' => Ztan::int()])->getMeta());
        self::assertNull($object->omit(['name'])->getMeta());
    }

    public function testMetaCanBeReAppliedAfterARebuild(): void
    {
        $meta = Ztan::string()
            ->meta(description: 'dropped')
            ->minLength(3)
            ->meta(description: 'applied last')
            ->getMeta();

        self::assertSame('applied last', $meta?->description);
    }

    public function testNullableWrapsTheDescribedInstance(): void
    {
        $described = Ztan::string()->meta(description: 'inner');
        $nullable = $described->nullable();

        self::assertNull($nullable->getMeta());
        self::assertSame('inner', $described->getMeta()?->description);
        self::assertSame('top level', $nullable->meta(description: 'top level')->getMeta()?->description);
    }

    public function testNonBaseTypeTypesSupportMeta(): void
    {
        self::assertSame('m', new MixedType()->meta(description: 'm')->getMeta()?->description);
        self::assertSame('n', new NeverType()->meta(description: 'n')->getMeta()?->description);
        self::assertSame('0', new NullType()->meta(description: '0')->getMeta()?->description);
    }

    public function testValidationIsUnaffectedByMeta(): void
    {
        $type = Ztan::string()->minLength(3)->meta(description: 'd');

        self::assertSame('abc', $type->parse('abc'));
        self::assertInstanceOf(ParseError::class, $type->safeParse('a'));
    }
}
