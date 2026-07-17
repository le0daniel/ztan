<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Integration\FullSchema\Schemas;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Tests\Integration\FullSchema\SchemaTestCase;
use Le0daniel\Ztan\Ztan;

/**
 * The canonical pipe() use-case: accept a JSON string, decode it, and validate
 * the decoded payload against a shape. json_decode deliberately does not throw —
 * malformed JSON decodes to null and fails the shape like any other bad value.
 */
final class JsonPayloadSchema implements SchemaTestCase
{
    public function schema(): BaseType
    {
        return Ztan::string()
            ->transform(fn (string $value): mixed => json_decode($value, true))
            ->pipe(Ztan::arrayShape([
                'name' => Ztan::string(),
                'age' => Ztan::int(),
                'tags?' => Ztan::list(Ztan::string()),
            ]));
    }

    public function jsonSchema(): ?array
    {
        return ['type' => 'string'];
    }

    public function passingValues(): iterable
    {
        yield 'full payload' => [
            '{"name":"leo","age":30,"tags":["a","b"]}',
            ['name' => 'leo', 'age' => 30, 'tags' => ['a', 'b']],
        ];
        yield 'without the optional tags key' => [
            '{"name":"leo","age":30}',
            ['name' => 'leo', 'age' => 30],
        ];
    }

    public function failingValues(): iterable
    {
        yield 'not a string' => [['name' => 'leo', 'age' => 30]];
        yield 'malformed json' => ['{invalid'];
        yield 'valid json with a wrong shape' => ['{"name":123,"age":"x"}'];
        yield 'json scalar' => ['"just a string"'];
        yield 'json null' => ['null'];
    }
}
