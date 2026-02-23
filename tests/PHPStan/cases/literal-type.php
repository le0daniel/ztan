<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\PHPStan\Cases;

use Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit;
use Le0daniel\Assertions\Types\Scalars\LiteralType;
use function PHPStan\Testing\assertType;

$stringType = new LiteralType('hello');

assertType("'hello'", $stringType->parse('x'));
assertType("Le0daniel\Assertions\Data\ParseError|Le0daniel\Assertions\Data\ParseSuccess<'hello'>", $stringType->safeParse('x'));

assertType("Le0daniel\Assertions\Types\NullableType<'hello'>", $stringType->nullable());
assertType("'hello'|null", $stringType->nullable()->parse('x'));

assertType("Le0daniel\Assertions\Types\CatchType<'hello'>", $stringType->catch('hello'));
assertType("'hello'", $stringType->catch('hello')->parse('x'));

assertType("Le0daniel\Assertions\Types\RefineType<'hello'>", $stringType->refine(fn(string $s) => $s !== '', 'msg'));
assertType("'hello'", $stringType->refine(fn(string $s) => $s !== '', 'msg')->parse('x'));

assertType("Le0daniel\Assertions\Types\PreprocessType<'hello'>", $stringType->preprocess(fn($v) => $v));

$intType = new LiteralType(42);
assertType('42', $intType->parse('x'));

$floatType = new LiteralType(3.14);
assertType('3.14', $floatType->parse('x'));

$trueType = new LiteralType(true);
assertType('true', $trueType->parse('x'));

$falseType = new LiteralType(false);
assertType('false', $falseType->parse('x'));

$enumType = new LiteralType(Suit::HEARTS);
assertType('Le0daniel\Assertions\Tests\PHPStan\Fixtures\Suit::HEARTS', $enumType->parse('x'));
