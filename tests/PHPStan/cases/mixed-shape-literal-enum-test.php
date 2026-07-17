<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Ztan;
use function PHPStan\Testing\assertType;

$schema = Ztan::arrayShape([
    'foo' => Ztan::string(),
    'cases' => Ztan::union(
        Ztan::literal(1),
        Ztan::literal("three"),
        Ztan::literal("two"),
    ),
    "other?" => Ztan::arrayShape([
        "type" => Ztan::union(
            Ztan::literal("string"),
            Ztan::literal("int"),
        ),
    ]),
]);

assertType("array{foo: string, cases: 1|'three'|'two', other?: array{type: 'int'|'string'}}", $schema->parse([]));
assertType("'int'|'string'|null", $schema->parse([])['other']['type'] ?? null);