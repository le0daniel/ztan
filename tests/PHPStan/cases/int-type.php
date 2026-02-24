<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Scalars\IntType;
use function PHPStan\Testing\assertType;

$type = new IntType();

assertType('int', $type->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<int>', $type->safeParse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<int>', $type->nullable());
assertType('int|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Ztan\Types\CatchType<int>', $type->catch(0));
assertType('int', $type->catch(0)->parse('x'));

assertType('Le0daniel\Ztan\Types\TransformType<int, lowercase-string&numeric-string&uppercase-string>', $type->transform(fn(int $v) => (string) $v));
assertType('lowercase-string&numeric-string&uppercase-string', $type->transform(fn(int $v) => (string) $v)->parse('x'));

assertType('Le0daniel\Ztan\Types\RefineType<int>', $type->refine(fn(int $v) => $v > 0, 'msg'));
assertType('int', $type->refine(fn(int $v) => $v > 0, 'msg')->parse('x'));

assertType('Le0daniel\Ztan\Types\PreprocessType<int>', $type->preprocess(fn($v) => (int) $v));
