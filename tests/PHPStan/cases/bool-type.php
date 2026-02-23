<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use Le0daniel\Assertions\Types\Scalars\BoolType;
use function PHPStan\Testing\assertType;

$type = new BoolType();

assertType('bool', $type->parse('x'));
assertType('Le0daniel\Assertions\Data\ParseError|Le0daniel\Assertions\Data\ParseSuccess<bool>', $type->safeParse('x'));

assertType('Le0daniel\Assertions\Types\NullableType<bool>', $type->nullable());
assertType('bool|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Assertions\Types\CatchType<bool>', $type->catch(false));
assertType('bool', $type->catch(false)->parse('x'));

assertType('Le0daniel\Assertions\Types\TransformType<bool, int>', $type->transform(fn(bool $v) => $v ? 1 : 0));
assertType('int', $type->transform(fn(bool $v) => $v ? 1 : 0)->parse('x'));

assertType('Le0daniel\Assertions\Types\RefineType<bool>', $type->refine(fn(bool $v) => $v === true, 'msg'));
assertType('bool', $type->refine(fn(bool $v) => $v === true, 'msg')->parse('x'));

assertType('Le0daniel\Assertions\Types\PreprocessType<bool>', $type->preprocess(fn($v) => (bool) $v));
