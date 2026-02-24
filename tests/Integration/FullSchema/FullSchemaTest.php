<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Integration\FullSchema;

use Le0daniel\Assertions\Data\ParseError;
use Le0daniel\Assertions\Data\ParseSuccess;
use Le0daniel\Assertions\Tests\Integration\FullSchema\Schemas\DeepSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FullSchemaTest extends TestCase
{
    /** @return list<SchemaTestCase> */
    private static function allCases(): array
    {
        return [
            new DeepSchema(),
        ];
    }

    public static function passingProvider(): iterable
    {
        foreach (self::allCases() as $case) {
            $label = $case::class;
            foreach ($case->passingValues() as $name => [$input, $expected]) {
                yield "{$label} :: {$name}" => [$case, $input, $expected];
            }
        }
    }

    public static function failingProvider(): iterable
    {
        foreach (self::allCases() as $case) {
            $label = $case::class;
            foreach ($case->failingValues() as $name => [$input]) {
                yield "{$label} :: {$name}" => [$case, $input];
            }
        }
    }

    public static function schemaProvider(): iterable
    {
        foreach (self::allCases() as $case) {
            yield $case::class => [$case];
        }
    }

    #[DataProvider('passingProvider')]
    public function testPassingValues(SchemaTestCase $case, mixed $input, mixed $expected): void
    {
        $result = $case->schema()->safeParse($input);
        self::assertInstanceOf(ParseSuccess::class, $result, 'Expected ParseSuccess but got ParseError: ' . ($result instanceof ParseError ? json_encode(array_map(fn($i) => $i->message, $result->issues)) : ''));
        self::assertSame($expected, $result->data);
    }

    #[DataProvider('failingProvider')]
    public function testFailingValues(SchemaTestCase $case, mixed $input): void
    {
        $result = $case->schema()->safeParse($input);
        self::assertInstanceOf(ParseError::class, $result, 'Expected ParseError but got ParseSuccess');
    }

    #[DataProvider('schemaProvider')]
    public function testExpectedPhpStanType(SchemaTestCase $case): void
    {
        self::assertNotEmpty($case->expectedPhpStanType(), 'expectedPhpStanType() must return a non-empty string');
    }
}
