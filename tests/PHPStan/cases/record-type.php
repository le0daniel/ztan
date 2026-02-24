<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$rec = new RecordType(new StringType());
assertType('array<string, string>', $rec->parse('x'));

$nested = new RecordType(new RecordType(new StringType()));
assertType('array<string, array<string, string>>', $nested->parse('x'));

$recOfShape = new RecordType(new ArrayShapeType(['id' => new StringType()]));
assertType('array<string, array{id: string}>', $recOfShape->parse('x'));

// Constraint methods preserve type
assertType('array<string, string>', $rec->nonEmpty()->parse('x'));
assertType('array<string, string>', $rec->minProperties(2)->parse('x'));
assertType('array<string, string>', $rec->maxProperties(5)->parse('x'));
assertType('array<string, string>', $rec->minProperties(1)->maxProperties(10)->parse('x'));

// Chained constraints compose with other methods
assertType('Le0daniel\Ztan\Types\NullableType<array<string, string>>', $rec->nonEmpty()->nullable());
assertType('array<string, string>|null', $rec->nonEmpty()->nullable()->parse('x'));
