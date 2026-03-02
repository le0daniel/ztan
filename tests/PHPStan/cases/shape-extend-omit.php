<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

// ── ArrayShapeType ──────────────────────────────────────────────────────────

$shape = new ArrayShapeType(['name' => new StringType(), 'age' => new IntType()]);

// extend: adds a new field
$extended = $shape->extend(['email' => new StringType()]);
assertType('array{name: string, age: int, email: string}', $extended->parse('x'));

// extend: overrides an existing field
$overridden = $shape->extend(['age' => new StringType()]);
assertType('array{name: string, age: string}', $overridden->parse('x'));

// extend: adds an optional new field
$withOptional = $shape->extend(['role?' => new StringType()]);
assertType('array{name: string, age: int, role?: string}', $withOptional->parse('x'));

// omit: removes a required field
$omitted = $shape->omit(['age']);
assertType('array{name: string}', $omitted->parse('x'));

// omit: removes an optional field
$optShape = new ArrayShapeType(['name' => new StringType(), 'age?' => new IntType()]);
$omittedOpt = $optShape->omit(['age']);
assertType('array{name: string}', $omittedOpt->parse('x'));

// chain: extend then omit
$chained = $shape->extend(['email' => new StringType()])->omit(['age']);
assertType('array{name: string, email: string}', $chained->parse('x'));

// ── ObjectShapeType ─────────────────────────────────────────────────────────

$obj = new ObjectShapeType(['name' => new StringType(), 'age' => new IntType()]);

// extend: adds a new field
$objExtended = $obj->extend(['email' => new StringType()]);
assertType('object{name: string, age: int, email: string}', $objExtended->parse('x'));

// extend: overrides an existing field
$objOverridden = $obj->extend(['age' => new StringType()]);
assertType('object{name: string, age: string}', $objOverridden->parse('x'));

// extend: adds an optional new field
$objWithOptional = $obj->extend(['role?' => new StringType()]);
assertType('object{name: string, age: int, role?: string}', $objWithOptional->parse('x'));

// omit: removes a required field
$objOmitted = $obj->omit(['age']);
assertType('object{name: string}', $objOmitted->parse('x'));

// omit: removes an optional field
$optObj = new ObjectShapeType(['name' => new StringType(), 'age?' => new IntType()]);
$objOmittedOpt = $optObj->omit(['age']);
assertType('object{name: string}', $objOmittedOpt->parse('x'));

// chain: extend then omit
$objChained = $obj->extend(['email' => new StringType()])->omit(['age']);
assertType('object{name: string, email: string}', $objChained->parse('x'));
