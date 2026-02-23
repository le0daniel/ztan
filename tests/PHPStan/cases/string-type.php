<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use Le0daniel\Assertions\Types\Scalars\StringType;
use function PHPStan\Testing\assertType;

$type = new StringType();

assertType('string', $type->parse('x'));
assertType('Le0daniel\Assertions\Data\ParseError|Le0daniel\Assertions\Data\ParseSuccess<string>', $type->safeParse('x'));

assertType('Le0daniel\Assertions\Types\NullableType<string>', $type->nullable());
assertType('string|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Assertions\Types\CatchType<string>', $type->catch('default'));
assertType('string', $type->catch('default')->parse('x'));

assertType('Le0daniel\Assertions\Types\TransformType<string, int<0, max>>', $type->transform(fn(string $s) => strlen($s)));
assertType('int<0, max>', $type->transform(fn(string $s) => strlen($s))->parse('x'));

assertType('Le0daniel\Assertions\Types\RefineType<string>', $type->refine(fn(string $s) => $s !== '', 'msg'));
assertType('string', $type->refine(fn(string $s) => $s !== '', 'msg')->parse('x'));

assertType('Le0daniel\Assertions\Types\PreprocessType<string>', $type->preprocess(fn($v) => trim($v)));
