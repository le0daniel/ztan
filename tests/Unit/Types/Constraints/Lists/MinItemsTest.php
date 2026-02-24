<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Lists;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Lists\MinItems;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MinItemsTest extends TestCase
{
    /**
     * @return iterable<string, array{list<mixed>, int}>
     */
    public static function validProvider(): iterable
    {
        yield 'exactly min' => [['a', 'b'], 2];
        yield 'above min' => [['a', 'b', 'c'], 2];
        yield 'empty with min 0' => [[], 0];
        yield 'one item with min 1' => [['a'], 1];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidCounts(array $input, int $min): void
    {
        $pipe = new MinItems($min);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{list<mixed>, int}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'empty with min 1' => [[], 1];
        yield 'one item with min 2' => [['a'], 2];
        yield 'two items with min 3' => [['a', 'b'], 3];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidCounts(array $input, int $min): void
    {
        $pipe = new MinItems($min);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too few items.', $context->issues[0]->message);
    }

    public function testExclusiveBoundary(): void
    {
        $pipe = new MinItems(2, including: false);
        $context = new ValidationContext();

        // Exactly 2 should fail with exclusive
        $result = $pipe->execute(['a', 'b'], $context);
        self::assertSame(Value::INVALID, $result);

        // 3 should pass
        $context2 = new ValidationContext();
        $result2 = $pipe->execute(['a', 'b', 'c'], $context2);
        self::assertSame(['a', 'b', 'c'], $result2);
    }

    public function testMetadata(): void
    {
        $pipe = new MinItems(3);
        $context = new ValidationContext();

        $pipe->execute(['a'], $context);

        self::assertSame(3, $context->issues[0]->metadata['min_items']);
        self::assertTrue($context->issues[0]->metadata['including']);
        self::assertSame(1, $context->issues[0]->metadata['actual_count']);
    }
}
