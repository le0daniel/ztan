<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Data\ValidationContext;
use Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit;
use Le0daniel\Ztan\Types\Complex\DiscriminatedUnionType;
use Le0daniel\Ztan\Types\Complex\UnionType;
use Le0daniel\Ztan\Types\RefineType;
use Le0daniel\Ztan\Types\TransformType;
use Le0daniel\Ztan\Ztan;
use function PHPStan\Testing\assertType;

// Behaviour once provided by dedicated extensions, now covered by PHPDoc generics
// (PHPStan >= 2.3) or by the shared InferredTemplates resolvers.

$shape = Ztan::arrayShape(['type' => Ztan::literal('a'), 'n' => Ztan::int()]);
$context = new ValidationContext();

// execute() returns the resolved value type or the INVALID sentinel
assertType("array{type: 'a', n: int}|Le0daniel\Ztan\Data\Value::INVALID", $shape->execute('x', $context));
assertType('Le0daniel\Ztan\Data\Value::INVALID|object{a: int}', Ztan::objectShape(['a' => Ztan::int()])->execute('x', $context));
assertType("'a'|Le0daniel\Ztan\Data\Value::INVALID", Ztan::literal('a')->execute('x', $context));

// literal factories keep the constant through the template defaults
assertType("Le0daniel\Ztan\Types\Scalars\LiteralType<'a'>", Ztan::literal('a'));
assertType('Le0daniel\Ztan\Types\Scalars\LiteralType<1.5>', Ztan::literal(1.5));
assertType('Le0daniel\Ztan\Types\Scalars\LiteralType<false>', Ztan::literal(false));
assertType('Le0daniel\Ztan\Types\Scalars\LiteralType<Le0daniel\Ztan\Tests\PHPStan\Fixtures\Suit::HEARTS>', Ztan::literal(Suit::HEARTS));
assertType("Le0daniel\Ztan\Types\Scalars\LiteralType<'a'>", Ztan::coerce()->literal('a'));
assertType('Le0daniel\Ztan\Types\Scalars\LiteralType<true>', Ztan::coerce()->literal(true));
/** @var 'x'|'y' $either */
$either = 'x';
assertType("'x'|'y'", Ztan::literal($either)->parse('x'));
/** @var string $anyString */
$anyString = 'x';
assertType('string', Ztan::literal($anyString)->parse('x'));

// closure parameters are typed from the wrapped schema
new RefineType($shape, function ($v) {
    assertType("array{type: 'a', n: int}", $v);
    return true;
}, 'msg');
new RefineType(Ztan::literal('a'), function ($v) {
    assertType("'a'", $v);
    return true;
}, 'msg');
new TransformType($shape, function ($v) {
    assertType("array{type: 'a', n: int}", $v);
    return 1;
});

// new TransformType() resolves like ->transform()
$outer = 5;
assertType('Le0daniel\Ztan\Types\TransformType<int, array{int}>', new TransformType(Ztan::int(), fn($v) => [$v]));
assertType('Le0daniel\Ztan\Types\TransformType<int, 5>', new TransformType(Ztan::int(), fn($v) => $outer));
assertType("Le0daniel\Ztan\Types\TransformType<int, 1|'x'>", new TransformType(Ztan::int(), function ($v) {
    if ($v > 0) {
        return 'x';
    }
    return 1;
}));
assertType("'a'", (new TransformType($shape, fn($v) => $v['type']))->parse('x'));
assertType("array{k: 'a', z: array{int}}", (new TransformType($shape, fn($v) => ['k' => $v['type'], 'z' => [$v['n']]]))->parse('x'));
assertType('array{int}', (new TransformType(transformFn: fn($v) => [$v], assertion: Ztan::int()))->parse('x'));

// unions keep literals of their members
assertType("Le0daniel\Ztan\Types\Complex\UnionType<'a'|int>", Ztan::union(Ztan::literal('a'), Ztan::int()));
assertType("Le0daniel\Ztan\Types\Complex\UnionType<'a'|'b'>", new UnionType(Ztan::literal('a'), Ztan::literal('b')));
assertType("Le0daniel\Ztan\Types\Complex\DiscriminatedUnionType<array{type: 'a', n: int}>", Ztan::discriminatedUnion('type', [$shape]));
assertType("array{type: 'a', n: int}", (new DiscriminatedUnionType('type', [$shape]))->parse('x'));
