<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\RecordType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$rec = new RecordType(new StringType());
assertType('array<string, string>', $rec->parse('x'));

$nested = new RecordType(new RecordType(new StringType()));
assertType('array<string, array<string, string>>', $nested->parse('x'));

$recOfShape = new RecordType(new ArrayShapeType(['id' => new StringType()]));
assertType('array<string, array{id: string}>', $recOfShape->parse('x'));
