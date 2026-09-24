<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit;
use Le0daniel\Ztan\Types\Scalars\EnumType;
use function PHPStan\Testing\assertType;

$type = new EnumType(Suit::class);

assertType('Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit', $type->parse('x'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit>', $type->safeParse('x'));

assertType('Le0daniel\Ztan\Types\NullableType<Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit>', $type->nullable());
assertType('Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit|null', $type->nullable()->parse('x'));

assertType('Le0daniel\Ztan\Types\CatchType<Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit>', $type->catch(Suit::HEARTS));
assertType('Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit', $type->catch(Suit::HEARTS)->parse('x'));

assertType("Le0daniel\\Ztan\\Types\\TransformType<Le0daniel\\Ztan\\Tests\\PHPStan\\Fixtures\\Suit, 'CLUBS'|'DIAMONDS'|'HEARTS'|'SPADES'>", $type->transform(fn(Suit $v) => $v->name));
assertType("'CLUBS'|'DIAMONDS'|'HEARTS'|'SPADES'", $type->transform(fn(Suit $v) => $v->name)->parse('x'));

assertType('Le0daniel\Ztan\Types\RefineType<Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit>', $type->refine(fn(Suit $v) => $v !== Suit::CLUBS, 'msg'));
assertType('Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit', $type->refine(fn(Suit $v) => $v !== Suit::CLUBS, 'msg')->parse('x'));

assertType('Le0daniel\Ztan\Types\PreprocessType<Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit>', $type->preprocess(fn($v) => $v));
