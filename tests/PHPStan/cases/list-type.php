<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\ListType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$list = new ListType(new StringType());

assertType('list<string>', $list->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<list<string>>', $list->safeParse('x'));

$nested = new ListType(new ListType(new StringType()));
assertType('list<list<string>>', $nested->parse('x'));

$withShape = new ListType(new ArrayShapeType(['name' => new StringType()]));
assertType('list<array{name: string}>', $withShape->parse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<list<string>>', $list->nullable());
assertType('list<string>|null', $list->nullable()->parse('x'));

assertType('Le0daniel\Ztan\Types\CatchType<list<string>>', $list->catch([]));
assertType('list<string>', $list->catch([])->parse('x'));

assertType('Le0daniel\Ztan\Types\RefineType<list<string>>', $list->refine(fn(array $v) => $v !== [], 'msg'));
assertType('list<string>', $list->refine(fn(array $v) => $v !== [], 'msg')->parse('x'));

assertType('Le0daniel\Ztan\Types\PreprocessType<list<string>>', $list->preprocess(fn($v) => $v));

// Constraint methods preserve type
assertType('list<string>', $list->nonEmpty()->parse('x'));
assertType('list<string>', $list->minItems(2)->parse('x'));
assertType('list<string>', $list->maxItems(5)->parse('x'));
assertType('list<string>', $list->length(3)->parse('x'));
assertType('list<string>', $list->minItems(1)->maxItems(10)->parse('x'));

// Chained constraints compose with other methods
assertType('Le0daniel\Ztan\Types\NullableType<list<string>>', $list->nonEmpty()->nullable());
assertType('list<string>|null', $list->nonEmpty()->nullable()->parse('x'));
