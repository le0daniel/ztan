<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\ListType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$list = new ListType(new StringType());

assertType('list<string>', $list->parse('x'));
assertType('Le0daniel\Assertions\Data\ParseError|Le0daniel\Assertions\Data\ParseSuccess<list<string>>', $list->safeParse('x'));

$nested = new ListType(new ListType(new StringType()));
assertType('list<list<string>>', $nested->parse('x'));

$withShape = new ListType(new ArrayShapeType(['name' => new StringType()]));
assertType('list<array{name: string}>', $withShape->parse('x'));

assertType('Le0daniel\Assertions\Types\NullableType<list<string>>', $list->nullable());
assertType('list<string>|null', $list->nullable()->parse('x'));

assertType('Le0daniel\Assertions\Types\CatchType<list<string>>', $list->catch([]));
assertType('list<string>', $list->catch([])->parse('x'));

assertType('Le0daniel\Assertions\Types\RefineType<list<string>>', $list->refine(fn(array $v) => $v !== [], 'msg'));
assertType('list<string>', $list->refine(fn(array $v) => $v !== [], 'msg')->parse('x'));

assertType('Le0daniel\Assertions\Types\PreprocessType<list<string>>', $list->preprocess(fn($v) => $v));
