<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Records;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Records\MinRecords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MinRecordsTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function validProvider(): iterable
    {
        yield 'exactly min' => [['a' => '1', 'b' => '2'], 2];
        yield 'above min' => [['a' => '1', 'b' => '2', 'c' => '3'], 2];
        yield 'empty with min 0' => [[], 0];
        yield 'one property with min 1' => [['a' => '1'], 1];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidCounts(array $input, int $min): void
    {
        $pipe = new MinRecords($min);
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
        yield 'empty with min 1' => [[], 1];
        yield 'one property with min 2' => [['a' => '1'], 2];
        yield 'two properties with min 3' => [['a' => '1', 'b' => '2'], 3];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidCounts(array $input, int $min): void
    {
        $pipe = new MinRecords($min);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too few properties.', $context->issues[0]->message);
    }

    public function testExclusiveBoundary(): void
    {
        $pipe = new MinRecords(2, including: false);
        $context = new ValidationContext();

        $result = $pipe->execute(['a' => '1', 'b' => '2'], $context);
        self::assertSame(Value::INVALID, $result);

        $context2 = new ValidationContext();
        $result2 = $pipe->execute(['a' => '1', 'b' => '2', 'c' => '3'], $context2);
        self::assertSame(['a' => '1', 'b' => '2', 'c' => '3'], $result2);
    }

    public function testMetadata(): void
    {
        $pipe = new MinRecords(3);
        $context = new ValidationContext();

        $pipe->execute(['a' => '1'], $context);

        self::assertSame(3, $context->issues[0]->metadata['min_properties']);
        self::assertTrue($context->issues[0]->metadata['including']);
        self::assertSame(1, $context->issues[0]->metadata['actual_count']);
    }
}
