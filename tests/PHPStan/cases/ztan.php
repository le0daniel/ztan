<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use DateTimeImmutable;
use Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit;
use Le0daniel\Assertions\Ztan;
use Le0daniel\Assertions\Types\Scalars\StringType;
use Le0daniel\Assertions\Types\Scalars\IntType;
use Le0daniel\Assertions\Types\Scalars\LiteralType;
use function PHPStan\Testing\assertType;

// Scalar types
assertType('string', Ztan::string()->parse('x'));
assertType('int', Ztan::int()->parse('x'));
assertType('float', Ztan::float()->parse('x'));
assertType('bool', Ztan::bool()->parse('x'));
assertType('mixed', Ztan::mixed()->execute('x', new \Le0daniel\Assertions\Data\ValidationContext()));
assertType('DateTimeImmutable', Ztan::dateTimeString('Y-m-d')->parse('x'));

// Template-forwarded types
assertType("'hello'", Ztan::literal('hello')->parse('x'));
assertType('42', Ztan::literal(42)->parse('x'));
assertType('Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit::HEARTS', Ztan::literal(Suit::HEARTS)->parse('x'));
assertType('Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit', Ztan::enum(Suit::class)->parse('x'));
assertType('DateTimeImmutable', Ztan::instance(DateTimeImmutable::class)->parse('x'));
assertType('list<string>', Ztan::list(new StringType())->parse('x'));
assertType('array<string, int>', Ztan::record(new IntType())->parse('x'));

// Complex types needing resolvers
assertType('array{name: string}', Ztan::arrayShape(['name' => new StringType()])->parse('x'));
assertType('array{name: string, age?: int}', Ztan::arrayShape(['name' => new StringType(), 'age?' => new IntType()])->parse('x'));

// Nested shapes
assertType('array{user: array{name: string}}', Ztan::arrayShape([
    'user' => Ztan::arrayShape(['name' => new StringType()]),
])->parse('x'));

// Object shape
assertType('object{name: string, age?: int}', Ztan::objectShape(['name' => new StringType(), 'age?' => new IntType()])->parse('x'));

// Union
assertType('int|string', Ztan::union(new StringType(), new IntType())->parse('x'));

// Tuple
assertType('array{string, int}', Ztan::tuple(new StringType(), new IntType())->parse('x'));

// Discriminated union
$du = Ztan::discriminatedUnion('type', [
    Ztan::arrayShape(['type' => new LiteralType('a'), 'name' => new StringType()]),
    Ztan::arrayShape(['type' => new LiteralType('b'), 'age' => new IntType()]),
]);
assertType('array{type: \'a\', name: string}|array{type: \'b\', age: int}', $du->parse('x'));

// Method chaining from facade
assertType('string', Ztan::string()->trim()->minLength(1)->parse('x'));

// Wrapper types from facade-created types
assertType('string|null', Ztan::string()->nullable()->parse('x'));
assertType('string', Ztan::string()->catch('fallback')->parse('x'));
assertType('int<0, max>', Ztan::string()->transform(fn(string $v) => strlen($v))->parse('x'));

// Coerce builder
assertType('Le0daniel\Assertions\CoerceBuilder', Ztan::coerce());
