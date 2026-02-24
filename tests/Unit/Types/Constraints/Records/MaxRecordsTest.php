<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Types\Constraints\Records;

use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Records\MaxRecords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MaxRecordsTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function validProvider(): iterable
    {
        yield 'exactly max' => [['a' => '1', 'b' => '2'], 2];
        yield 'below max' => [['a' => '1'], 2];
        yield 'empty with max 0' => [[], 0];
        yield 'empty with max 3' => [[], 3];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidCounts(array $input, int $max): void
    {
        $pipe = new MaxRecords($max);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'one property with max 0' => [['a' => '1'], 0];
        yield 'three properties with max 2' => [['a' => '1', 'b' => '2', 'c' => '3'], 2];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidCounts(array $input, int $max): void
    {
        $pipe = new MaxRecords($max);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too many properties.', $context->issues[0]->message);
    }

    public function testExclusiveBoundary(): void
    {
        $pipe = new MaxRecords(2, including: false);
        $context = new ValidationContext();

        $result = $pipe->execute(['a' => '1', 'b' => '2'], $context);
        self::assertSame(Value::INVALID, $result);

        $context2 = new ValidationContext();
        $result2 = $pipe->execute(['a' => '1'], $context2);
        self::assertSame(['a' => '1'], $result2);
    }

    public function testMetadata(): void
    {
        $pipe = new MaxRecords(1);
        $context = new ValidationContext();

        $pipe->execute(['a' => '1', 'b' => '2', 'c' => '3'], $context);

        self::assertSame(1, $context->issues[0]->metadata['max_properties']);
        self::assertTrue($context->issues[0]->metadata['including']);
        self::assertSame(3, $context->issues[0]->metadata['actual_count']);
    }
}
