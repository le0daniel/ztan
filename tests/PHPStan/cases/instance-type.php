<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use DateTimeImmutable;
use Le0daniel\Assertions\Types\Scalars\InstanceType;
use function PHPStan\Testing\assertType;

$type = new InstanceType(DateTimeImmutable::class);

assertType('DateTimeImmutable', $type->parse('x'));
assertType('Le0daniel\Assertions\Data\ParseError|Le0daniel\Assertions\Data\ParseSuccess<DateTimeImmutable>', $type->safeParse('x'));

assertType('Le0daniel\Assertions\Types\NullableType<DateTimeImmutable>', $type->nullable());
assertType('DateTimeImmutable|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Assertions\Types\CatchType<DateTimeImmutable>', $type->catch(new DateTimeImmutable()));
assertType('DateTimeImmutable', $type->catch(new DateTimeImmutable())->parse('x'));

assertType('Le0daniel\Assertions\Types\TransformType<DateTimeImmutable, non-falsy-string>', $type->transform(fn(DateTimeImmutable $v) => $v->format('Y-m-d')));
assertType('non-falsy-string', $type->transform(fn(DateTimeImmutable $v) => $v->format('Y-m-d'))->parse('x'));

assertType('Le0daniel\Assertions\Types\RefineType<DateTimeImmutable>', $type->refine(fn(DateTimeImmutable $v) => $v > new DateTimeImmutable(), 'msg'));
assertType('DateTimeImmutable', $type->refine(fn(DateTimeImmutable $v) => $v > new DateTimeImmutable(), 'msg')->parse('x'));

assertType('Le0daniel\Assertions\Types\PreprocessType<DateTimeImmutable>', $type->preprocess(fn($v) => $v));
