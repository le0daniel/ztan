<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Integration\FullSchema;

use Le0daniel\Ztan\Data\ParseError;
use Le0daniel\Ztan\Data\ParseSuccess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FullSchemaTest extends TestCase
{
    /** @return list<SchemaTestCase> */
    public static function allCases(): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . '/Schemas'));
        $classNames = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $classNames[] = 'Le0daniel\\Ztan\\Tests\\Integration\\FullSchema\\Schemas\\' . $file->getBasename('.php');
        }

        return array_map(fn($className) => new $className(), $classNames);
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
}
