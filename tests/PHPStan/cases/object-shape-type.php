<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$shape = new ObjectShapeType(['name' => new StringType()]);
assertType('object{name: string}', $shape->parse('x'));

$opt = new ObjectShapeType(['name' => new StringType(), 'age?' => new StringType()]);
assertType('object{name: string, age?: string}', $opt->parse('x'));

$nested = new ObjectShapeType(['user' => new ObjectShapeType(['name' => new StringType()])]);
assertType('object{user: object{name: string}}', $nested->parse('x'));

$crossNested = new ObjectShapeType(['tags' => new ArrayShapeType(['key' => new StringType()])]);
assertType('object{tags: array{key: string}}', $crossNested->parse('x'));
