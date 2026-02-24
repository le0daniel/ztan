<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\Scalars\FloatType;
use function PHPStan\Testing\assertType;

$type = new FloatType();

assertType('float', $type->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<float>', $type->safeParse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<float>', $type->nullable());
assertType('float|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Ztan\Types\CatchType<float>', $type->catch(0.0));
assertType('float', $type->catch(0.0)->parse('x'));

assertType('Le0daniel\Ztan\Types\TransformType<float, int>', $type->transform(fn(float $v) => (int) $v));
assertType('int', $type->transform(fn(float $v) => (int) $v)->parse('x'));

assertType('Le0daniel\Ztan\Types\RefineType<float>', $type->refine(fn(float $v) => $v > 0, 'msg'));
assertType('float', $type->refine(fn(float $v) => $v > 0, 'msg')->parse('x'));

assertType('Le0daniel\Ztan\Types\PreprocessType<float>', $type->preprocess(fn($v) => (float) $v));
