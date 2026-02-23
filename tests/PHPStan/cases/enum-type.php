<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit;
use Le0daniel\Assertions\Types\Scalars\EnumType;
use function PHPStan\Testing\assertType;

$type = new EnumType(Suit::class);

assertType('Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit', $type->parse('x'));
assertType('Le0daniel\Assertions\Data\ParseError|Le0daniel\Assertions\Data\ParseSuccess<Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit>', $type->safeParse('x'));

assertType('Le0daniel\Assertions\Types\NullableType<Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit>', $type->nullable());
assertType('Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Assertions\Types\CatchType<Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit>', $type->catch(Suit::HEARTS));
assertType('Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit', $type->catch(Suit::HEARTS)->parse('x'));

assertType('Le0daniel\Assertions\Types\TransformType<Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit, string>', $type->transform(fn(Suit $v) => $v->name));
assertType('string', $type->transform(fn(Suit $v) => $v->name)->parse('x'));

assertType('Le0daniel\Assertions\Types\RefineType<Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit>', $type->refine(fn(Suit $v) => $v !== Suit::CLUBS, 'msg'));
assertType('Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit', $type->refine(fn(Suit $v) => $v !== Suit::CLUBS, 'msg')->parse('x'));

assertType('Le0daniel\Assertions\Types\PreprocessType<Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit>', $type->preprocess(fn($v) => $v));
