<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit;
use Le0daniel\Ztan\Ztan;
use function PHPStan\Testing\assertType;

$coerce = Ztan::coerce();

assertType('string', $coerce->string()->parse('x'));
assertType('int', $coerce->int()->parse('x'));
assertType('float', $coerce->float()->parse('x'));
assertType('bool', $coerce->bool()->parse('x'));

assertType("'hello'", $coerce->literal('hello')->parse('x'));
assertType('42', $coerce->literal(42)->parse('x'));
assertType('Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit::HEARTS', $coerce->literal(Suit::HEARTS)->parse('x'));

assertType('Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit', $coerce->enum(Suit::class)->parse('x'));

// Chaining from coerce builder
assertType('string', Ztan::coerce()->string()->trim()->minLength(1)->parse('x'));
assertType('string|null', Ztan::coerce()->string()->nullable()->parse('x'));
