<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Integration\FullSchema\Schemas;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Tests\Integration\FullSchema\SchemaTestCase;
use Le0daniel\Ztan\Ztan;

final class DeepSchema implements SchemaTestCase
{
    public function schema(): BaseType
    {
        return Ztan::arrayShape([
            'name' => Ztan::string()->trim()->notEmpty()->maxLength(100),
            'email' => Ztan::string()->email(),
            'age' => Ztan::int()->range(0, 150),
            'score' => Ztan::float()->gte(0.0),
            'isActive' => Ztan::bool(),
            'role' => Ztan::literal('admin'),
            'tags' => Ztan::list(Ztan::string()->notEmpty())->minItems(1)->maxItems(5),
            'metadata' => Ztan::record(Ztan::union(Ztan::string(), Ztan::int())),
            'address' => Ztan::arrayShape([
                'street' => Ztan::string()->notEmpty(),
                'city' => Ztan::string()->notEmpty(),
                'zip' => Ztan::string()->regex('/^\d{5}$/'),
                'country?' => Ztan::string(),
            ]),
            'bio?' => Ztan::string()->maxLength(500)->nullable(),
            'coordinates' => Ztan::tuple(Ztan::float(), Ztan::float()),
            'status' => Ztan::discriminatedUnion('type', [
                Ztan::arrayShape([
                    'type' => Ztan::literal('active'),
                    'since' => Ztan::string(),
                ]),
                Ztan::arrayShape([
                    'type' => Ztan::literal('inactive'),
                    'reason' => Ztan::string(),
                ]),
            ]),
            'nameLength' => Ztan::string()->transform(fn(string $v): int => strlen($v)),
        ]);
    }

    public function passingValues(): iterable
    {
        $base = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => 30,
            'score' => 95.5,
            'isActive' => true,
            'role' => 'admin',
            'tags' => ['php', 'testing'],
            'metadata' => ['level' => 'senior', 'years' => 10],
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip' => '12345',
            ],
            'coordinates' => [40.7128, -74.0060],
            'status' => ['type' => 'active', 'since' => '2024-01-01'],
            'nameLength' => 'Alice',
        ];

        yield 'minimal valid (required fields only)' => [
            $base,
            [
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => 30,
                'score' => 95.5,
                'isActive' => true,
                'role' => 'admin',
                'tags' => ['php', 'testing'],
                'metadata' => ['level' => 'senior', 'years' => 10],
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'zip' => '12345',
                ],
                'coordinates' => [40.7128, -74.0060],
                'status' => ['type' => 'active', 'since' => '2024-01-01'],
                'nameLength' => 5,
            ],
        ];

        yield 'full valid (all optional fields present)' => [
            array_merge($base, [
                'bio' => 'Software engineer',
                'address' => [
                    'street' => '456 Oak Ave',
                    'city' => 'Portland',
                    'zip' => '97201',
                    'country' => 'US',
                ],
                'status' => ['type' => 'inactive', 'reason' => 'vacation'],
            ]),
            [
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'age' => 30,
                'score' => 95.5,
                'isActive' => true,
                'role' => 'admin',
                'tags' => ['php', 'testing'],
                'metadata' => ['level' => 'senior', 'years' => 10],
                'address' => [
                    'street' => '456 Oak Ave',
                    'city' => 'Portland',
                    'zip' => '97201',
                    'country' => 'US',
                ],
                'bio' => 'Software engineer',
                'coordinates' => [40.7128, -74.0060],
                'status' => ['type' => 'inactive', 'reason' => 'vacation'],
                'nameLength' => 5,
            ],
        ];

        yield 'edge cases (trim, boundary values, nullable bio)' => [
            array_merge($base, [
                'name' => '  Bob  ',
                'age' => 0,
                'score' => 0.0,
                'tags' => ['a'],
                'metadata' => [],
                'bio' => null,
                'nameLength' => '  Bob  ',
            ]),
            [
                'name' => 'Bob',
                'email' => 'alice@example.com',
                'age' => 0,
                'score' => 0.0,
                'isActive' => true,
                'role' => 'admin',
                'tags' => ['a'],
                'metadata' => [],
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'Springfield',
                    'zip' => '12345',
                ],
                'bio' => null,
                'coordinates' => [40.7128, -74.0060],
                'status' => ['type' => 'active', 'since' => '2024-01-01'],
                'nameLength' => 7,
            ],
        ];
    }

    public function failingValues(): iterable
    {
        $base = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => 30,
            'score' => 95.5,
            'isActive' => true,
            'role' => 'admin',
            'tags' => ['php'],
            'metadata' => [],
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip' => '12345',
            ],
            'coordinates' => [40.7128, -74.0060],
            'status' => ['type' => 'active', 'since' => '2024-01-01'],
            'nameLength' => 'Alice',
        ];

        yield 'wrong type for name (int instead of string)' => [
            array_merge($base, ['name' => 42]),
        ];

        yield 'missing required field (no email)' => [
            array_diff_key($base, ['email' => true]),
        ];

        yield 'string too long (name > 100 chars)' => [
            array_merge($base, ['name' => str_repeat('a', 101)]),
        ];

        yield 'list too many items (tags > 5)' => [
            array_merge($base, ['tags' => ['a', 'b', 'c', 'd', 'e', 'f']]),
        ];

        yield 'list too few items (tags empty)' => [
            array_merge($base, ['tags' => []]),
        ];

        yield 'invalid email' => [
            array_merge($base, ['email' => 'not-an-email']),
        ];

        yield 'age out of range (negative)' => [
            array_merge($base, ['age' => -1]),
        ];

        yield 'age out of range (too high)' => [
            array_merge($base, ['age' => 151]),
        ];

        yield 'invalid nested structure (bad zip)' => [
            array_merge($base, ['address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip' => 'ABCDE',
            ]]),
        ];

        yield 'invalid discriminated union discriminator' => [
            array_merge($base, ['status' => ['type' => 'unknown', 'data' => 'x']]),
        ];

        yield 'regex mismatch (zip not 5 digits)' => [
            array_merge($base, ['address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip' => '123',
            ]]),
        ];

        yield 'wrong role literal' => [
            array_merge($base, ['role' => 'user']),
        ];

        yield 'not an array' => [
            'just a string',
        ];

        yield 'empty name after trim' => [
            array_merge($base, ['name' => '   ']),
        ];

        yield 'negative score' => [
            array_merge($base, ['score' => -0.1]),
        ];
    }
}
