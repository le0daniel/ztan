<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\MixedType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use Le0daniel\Ztan\Ztan;
use function PHPStan\Testing\assertType;

// meta() returns the same concrete type (static return) — the chain keeps its class
assertType('Le0daniel\Ztan\Types\Scalars\StringType', Ztan::string()->meta(description: 'x'));
assertType('Le0daniel\Ztan\Types\Scalars\MixedType', new MixedType()->meta(description: 'x'));

// value types flow through a meta() hop
assertType('string', Ztan::string()->meta(description: 'x')->parse('x'));
assertType('string', Ztan::string()->minLength(2)->meta(description: 'x')->parse('x'));
assertType('list<string>', Ztan::list(Ztan::string())->meta(description: 'x')->parse('x'));

// shapes: the synthetic TProperties generic survives meta()
$shape = Ztan::arrayShape(['name' => new StringType()]);
assertType('array{name: string}', $shape->meta(description: 'x')->parse('x'));
assertType('array{name: string, age: int}', $shape->meta(description: 'x')->extend(['age' => new IntType()])->parse('x'));

// objectShape
assertType('object{name: string}', Ztan::objectShape(['name' => new StringType()])->meta(description: 'x')->parse('x'));
