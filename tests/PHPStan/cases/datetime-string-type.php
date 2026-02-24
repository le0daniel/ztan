<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use DateTimeImmutable;
use Le0daniel\Ztan\Types\Scalars\DateTimeStringType;
use function PHPStan\Testing\assertType;

$type = new DateTimeStringType('Y-m-d');

assertType('DateTimeImmutable', $type->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<DateTimeImmutable>', $type->safeParse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<DateTimeImmutable>', $type->nullable());
assertType('DateTimeImmutable|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Ztan\Types\CatchType<DateTimeImmutable>', $type->catch(new DateTimeImmutable()));
assertType('DateTimeImmutable', $type->catch(new DateTimeImmutable())->parse('x'));

assertType('Le0daniel\Ztan\Types\TransformType<DateTimeImmutable, non-falsy-string>', $type->transform(fn(DateTimeImmutable $v) => $v->format('Y-m-d')));
assertType('non-falsy-string', $type->transform(fn(DateTimeImmutable $v) => $v->format('Y-m-d'))->parse('x'));

assertType('Le0daniel\Ztan\Types\RefineType<DateTimeImmutable>', $type->refine(fn(DateTimeImmutable $v) => $v > new DateTimeImmutable(), 'msg'));
assertType('DateTimeImmutable', $type->refine(fn(DateTimeImmutable $v) => $v > new DateTimeImmutable(), 'msg')->parse('x'));

assertType('Le0daniel\Ztan\Types\PreprocessType<DateTimeImmutable>', $type->preprocess(fn($v) => $v));

assertType('Le0daniel\Ztan\Types\Scalars\DateTimeStringType', $type->after(new DateTimeImmutable()));
assertType('Le0daniel\Ztan\Types\Scalars\DateTimeStringType', $type->before(new DateTimeImmutable()));
assertType('Le0daniel\Ztan\Types\Scalars\DateTimeStringType', $type->between(new DateTimeImmutable(), new DateTimeImmutable()));
assertType('Le0daniel\Ztan\Types\Scalars\DateTimeStringType', $type->past());
assertType('Le0daniel\Ztan\Types\Scalars\DateTimeStringType', $type->future());
