<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Scalars;

use DateTimeImmutable;
use DateTimeInterface;
use Le0daniel\Ztan\Data\IssueType;
use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Scalars\InstanceType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class InstanceTypeTest extends TestCase
{
    public function testAcceptsCorrectInstance(): void
    {
        $type = new InstanceType(stdClass::class);
        $context = new ValidationContext();
        $input = new stdClass();

        $result = $type->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    public function testAcceptsSubclass(): void
    {
        $type = new InstanceType(DateTimeInterface::class);
        $context = new ValidationContext();
        $input = new DateTimeImmutable();

        $result = $type->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'string' => ['hello'];
        yield 'int' => [42];
        yield 'float' => [3.14];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'array' => [[]];
        yield 'wrong class' => [new stdClass()];
    }

    #[DataProvider('invalidInputProvider')]
    public function testRejectsInvalidValues(mixed $input): void
    {
        $type = new InstanceType(DateTimeImmutable::class);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Expected instance of DateTimeImmutable.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidType, $context->issues[0]->type);
        self::assertSame(DateTimeImmutable::class, $context->issues[0]->metadata['expected_class']);
    }
}
