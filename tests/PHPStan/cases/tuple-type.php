<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\TupleType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$tuple = new TupleType(new StringType(), new IntType());

assertType('array{string, int}', $tuple->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<array{string, int}>', $tuple->safeParse('x'));

$single = new TupleType(new StringType());
assertType('array{string}', $single->parse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<array{string, int}>', $tuple->nullable());
assertType('array{string, int}|null', $tuple->nullable()->parse('x'));

assertType('Le0daniel\Ztan\Types\CatchType<array{string, int}>', $tuple->catch([]));
assertType('array{string, int}', $tuple->catch([])->parse('x'));

assertType('Le0daniel\Ztan\Types\RefineType<array{string, int}>', $tuple->refine(fn(array $v) => true, 'msg'));
assertType('array{string, int}', $tuple->refine(fn(array $v) => true, 'msg')->parse('x'));

$withShape = new TupleType(
    new ArrayShapeType(['name' => new StringType()]),
    new IntType(),
);
assertType('array{array{name: string}, int}', $withShape->parse('x'));
