<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\UnionType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$union = new UnionType(new StringType(), new IntType());

assertType('int|string', $union->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<int|string>', $union->safeParse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<int|string>', $union->nullable());
assertType('int|string|null', $union->nullable()->parse('x'));

assertType('Le0daniel\Ztan\Types\CatchType<int|string>', $union->catch('fallback'));
assertType('int|string', $union->catch('fallback')->parse('x'));

assertType('Le0daniel\Ztan\Types\RefineType<int|string>', $union->refine(fn(int|string $v) => $v !== '', 'msg'));
assertType('int|string', $union->refine(fn(int|string $v) => $v !== '', 'msg')->parse('x'));

assertType('Le0daniel\Ztan\Types\PreprocessType<int|string>', $union->preprocess(fn($v) => $v));

$withShape = new UnionType(
    new ArrayShapeType(['name' => new StringType()]),
    new IntType(),
);
assertType('array{name: string}|int', $withShape->parse('x'));

$single = new UnionType(new StringType());
assertType('string', $single->parse('x'));
