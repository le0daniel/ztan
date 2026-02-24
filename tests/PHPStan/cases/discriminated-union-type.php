<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\DiscriminatedUnionType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\LiteralType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$du = new DiscriminatedUnionType('type', [
    new ArrayShapeType(['type' => new LiteralType('a'), 'name' => new StringType()]),
    new ArrayShapeType(['type' => new LiteralType('b'), 'age' => new IntType()]),
]);

assertType('array{type: \'a\', name: string}|array{type: \'b\', age: int}', $du->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<array{type: \'a\', name: string}|array{type: \'b\', age: int}>', $du->safeParse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<array{type: \'a\', name: string}|array{type: \'b\', age: int}>', $du->nullable());
assertType('array{type: \'a\', name: string}|array{type: \'b\', age: int}|null', $du->nullable()->parse('x'));

$single = new DiscriminatedUnionType('kind', [
    new ArrayShapeType(['kind' => new LiteralType('x'), 'value' => new StringType()]),
]);
assertType('array{kind: \'x\', value: string}', $single->parse('x'));
