<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types;

use JsonException;
use Le0daniel\Ztan\Data\ParseError;
use Le0daniel\Ztan\Data\ParseSuccess;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\ValidationException;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\PipeType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\NeverType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use Le0daniel\Ztan\Ztan;
use PHPUnit\Framework\TestCase;

final class PipeTypeTest extends TestCase
{
    public function testFirstTypesOutputFlowsIntoTheSecondType(): void
    {
        $type = new StringType()->trim()->pipe(new StringType()->minLength(3));

        $result = $type->safeParse('  abc  ');

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertSame('abc', $result->data);
    }

    public function testSecondTypeValidatesTheFirstTypesOutputNotTheRawInput(): void
    {
        // The raw input has length 6; trimmed it has length 2 and fails minLength(3).
        $type = new StringType()->trim()->pipe(new StringType()->minLength(3));

        $result = $type->safeParse('  ab  ');

        self::assertInstanceOf(ParseError::class, $result);
        self::assertSame('String is too short.', $result->issues[0]->message);
    }

    public function testFirstTypeFailureShortCircuitsTheSecondType(): void
    {
        $type = new PipeType(new StringType(), new NeverType());
        $context = new ValidationContext();

        $result = $type->execute(42, $context);

        self::assertSame(Value::INVALID, $result);
        // Only the StringType issue — NeverType would have added its own if executed.
        self::assertCount(1, $context->issues);
        self::assertSame('Expected string.', $context->issues[0]->message);
    }

    public function testSecondTypeFailureReportsItsIssues(): void
    {
        $type = new PipeType(new StringType(), new IntType());
        $context = new ValidationContext();

        $result = $type->execute('abc', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected integer.', $context->issues[0]->message);
        self::assertSame('abc', $context->issues[0]->received);
    }

    public function testIssuesInsideAShapePropertyKeepThePath(): void
    {
        $schema = Ztan::arrayShape([
            'payload' => Ztan::string()->pipe(Ztan::string()->minLength(5)),
        ]);

        $result = $schema->safeParse(['payload' => 'abc']);

        self::assertInstanceOf(ParseError::class, $result);
        self::assertSame(['payload'], $result->issues[0]->path);
    }

    public function testChainedPipesRunLeftToRight(): void
    {
        $type = Ztan::string()->pipe(Ztan::string())->pipe(Ztan::string()->minLength(2));

        $passing = $type->safeParse('ab');
        self::assertInstanceOf(ParseSuccess::class, $passing);
        self::assertSame('ab', $passing->data);

        $failing = $type->safeParse('a');
        self::assertInstanceOf(ParseError::class, $failing);
        self::assertCount(1, $failing->issues);
    }

    public function testPipeMethodWrapsReceiverAndTarget(): void
    {
        $first = Ztan::string();
        $second = Ztan::int();

        $pipe = $first->pipe($second);

        self::assertInstanceOf(PipeType::class, $pipe);
        self::assertSame($first, $pipe->firstType);
        self::assertSame($second, $pipe->secondType);
    }

    public function testParseThrowsOnFailure(): void
    {
        $type = new PipeType(new StringType(), new IntType());

        $this->expectException(ValidationException::class);
        $type->parse('abc');
    }

    public function testCompositionWithNullable(): void
    {
        $type = Ztan::string()->pipe(Ztan::string()->minLength(3))->nullable();

        self::assertNull($type->parse(null));
        self::assertSame('abc', $type->parse('abc'));

        $result = $type->safeParse('ab');
        self::assertInstanceOf(ParseError::class, $result);
    }

    public function testCompositionWithCatch(): void
    {
        $type = Ztan::string()->pipe(Ztan::string()->minLength(3))->catch('fallback');

        $result = $type->safeParse('ab');

        self::assertInstanceOf(ParseSuccess::class, $result);
        self::assertSame('fallback', $result->data);
        self::assertTrue($result->isPartial());
    }

    public function testRebuildingViaPipeDropsTheReceiversMeta(): void
    {
        $described = Ztan::string()->meta(description: 'raw');

        $pipe = $described->pipe(Ztan::int());

        self::assertNull($pipe->getMeta());
        self::assertSame('raw', $described->getMeta()?->description);
        self::assertSame('top', $pipe->meta(description: 'top')->getMeta()?->description);
    }

    public function testAThrowingTransformClosureFailsTheParseCleanly(): void
    {
        $schema = Ztan::string()
            ->transform(fn (string $value): mixed => json_decode($value, flags: JSON_THROW_ON_ERROR))
            ->pipe(Ztan::arrayShape(['name' => Ztan::string()]));

        $result = $schema->safeParse('not json');

        self::assertInstanceOf(ParseError::class, $result);
        self::assertSame('Internal error', $result->issues[0]->message);
        self::assertSame(JsonException::class, $result->issues[0]->metadata['exception']['class']);
    }
}
