<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Lists;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Lists\MaxItems;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MaxItemsTest extends TestCase
{
    /**
     * @return iterable<string, array{list<mixed>, int}>
     */
    public static function validProvider(): iterable
    {
        yield 'exactly max' => [['a', 'b'], 2];
        yield 'below max' => [['a'], 2];
        yield 'empty with max 0' => [[], 0];
        yield 'empty with max 3' => [[], 3];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidCounts(array $input, int $max): void
    {
        $pipe = new MaxItems($max);
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
        yield 'one item with max 0' => [['a'], 0];
        yield 'three items with max 2' => [['a', 'b', 'c'], 2];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidCounts(array $input, int $max): void
    {
        $pipe = new MaxItems($max);
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Too many items.', $context->issues[0]->message);
    }

    public function testExclusiveBoundary(): void
    {
        $pipe = new MaxItems(2, including: false);
        $context = new ValidationContext();

        // Exactly 2 should fail with exclusive
        $result = $pipe->execute(['a', 'b'], $context);
        self::assertSame(Value::INVALID, $result);

        // 1 should pass
        $context2 = new ValidationContext();
        $result2 = $pipe->execute(['a'], $context2);
        self::assertSame(['a'], $result2);
    }

    public function testMetadata(): void
    {
        $pipe = new MaxItems(1);
        $context = new ValidationContext();

        $pipe->execute(['a', 'b', 'c'], $context);

        self::assertSame(1, $context->issues[0]->metadata['max_items']);
        self::assertTrue($context->issues[0]->metadata['including']);
        self::assertSame(3, $context->issues[0]->metadata['actual_count']);
    }
}
