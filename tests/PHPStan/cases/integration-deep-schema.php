<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use Le0daniel\Assertions\Ztan;
use function PHPStan\Testing\assertType;

$schema = Ztan::arrayShape([
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

assertType("array{name: string, email: string, age: int, score: float, isActive: bool, role: 'admin', tags: list<string>, metadata: array<string, int|string>, address: array{street: string, city: string, zip: string, country?: string}, bio?: string|null, coordinates: array{float, float}, status: array{type: 'active', since: string}|array{type: 'inactive', reason: string}, nameLength: int<0, max>}", $schema->parse('x'));
