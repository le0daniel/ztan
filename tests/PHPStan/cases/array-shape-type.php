<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$shape = new ArrayShapeType(['name' => new StringType()]);
assertType('array{name: string}', $shape->parse('x'));

$opt = new ArrayShapeType(['name' => new StringType(), 'age?' => new StringType()]);
assertType('array{name: string, age?: string}', $opt->parse('x'));

$nested = new ArrayShapeType(['user' => new ArrayShapeType(['name' => new StringType()])]);
assertType('array{user: array{name: string}}', $nested->parse('x'));

$withRec = new ArrayShapeType(['tags' => new RecordType(new StringType())]);
assertType('array{tags: array<string, string>}', $withRec->parse('x'));

$tf = $shape->transform(fn($v) => $v['name']);
assertType('string', $tf->parse('x'));
