<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Types\CatchType;
use Le0daniel\Ztan\Types\Complex\ListType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\NullableType;
use Le0daniel\Ztan\Types\PipeType;
use Le0daniel\Ztan\Types\PreprocessType;
use Le0daniel\Ztan\Types\RefineType;
use Le0daniel\Ztan\Ztan;
use function PHPStan\Testing\assertType;

// PHPStan generalizes constant types when it infers a template from an argument
// ('a' → string). Schema outputs must keep them, or a discriminatedUnion nested in
// a list/record/pipe/transform can no longer be narrowed on its discriminator.

$event = Ztan::discriminatedUnion('type', [
    Ztan::arrayShape(['type' => Ztan::literal('a'), 'name' => Ztan::string()]),
    Ztan::arrayShape(['type' => Ztan::literal('b'), 'age' => Ztan::int()]),
]);
$shape = Ztan::arrayShape(['type' => Ztan::literal('a')]);

// Ztan::list() / Ztan::record()
assertType("Le0daniel\Ztan\Types\Complex\ListType<array{type: 'a', name: string}|array{type: 'b', age: int}>", Ztan::list($event));
assertType("list<array{type: 'a', name: string}|array{type: 'b', age: int}>", Ztan::list($event)->parse('x'));
assertType("array<string, array{type: 'a', name: string}|array{type: 'b', age: int}>", Ztan::record($event)->parse('x'));
assertType("list<'a'>", Ztan::list(Ztan::literal('a'))->parse('x'));
assertType('list<5>', Ztan::list(Ztan::literal(5))->parse('x'));
assertType("list<'a'|'b'>", Ztan::list(Ztan::union(Ztan::literal('a'), Ztan::literal('b')))->parse('x'));
assertType("list<list<'a'>>", Ztan::list(Ztan::list(Ztan::literal('a')))->parse('x'));
assertType("array<string, list<'a'>>", Ztan::record(Ztan::list(Ztan::literal('a')))->parse('x'));
assertType("list<array{type: 'a'}>|null", Ztan::list($shape)->nonEmpty()->nullable()->parse('x'));
assertType("array{items: list<array{type: 'a'}>}", Ztan::arrayShape(['items' => Ztan::list($shape)])->parse('x'));
assertType("list<array{type: 'a'}>", Ztan::list(type: $shape)->parse('x'));

// the reported scenario: elements of a parsed list narrow on the discriminator
foreach (Ztan::list($event)->parse('x') as $item) {
    assertType("'a'|'b'", $item['type']);
    if ($item['type'] === 'a') {
        assertType("array{type: 'a', name: string}", $item);
    } else {
        assertType("array{type: 'b', age: int}", $item);
    }
}
foreach (Ztan::record($event)->parse('x') as $item) {
    assertType("'a'|'b'", $item['type']);
}

// ->pipe()
assertType("Le0daniel\Ztan\Types\PipeType<string, array{type: 'a'}>", Ztan::string()->pipe($shape));
assertType("array{type: 'a', name: string}|array{type: 'b', age: int}", Ztan::string()->pipe($event)->parse('x'));
assertType("'a'", $event->pipe(Ztan::literal('a'))->parse('x'));

// ->transform()
assertType("array{type: 'a'}", $shape->transform(fn(array $v) => $v)->parse('x'));
assertType("'a'", $shape->transform(fn(array $v) => $v['type'])->parse('x'));
assertType("'a'|'b'", $event->transform(fn(array $v) => $v['type'])->parse('x'));
assertType("'const'", Ztan::string()->transform(fn(string $v) => 'const')->parse('x'));
assertType('int<0, max>', Ztan::string()->transform(fn(string $v) => strlen($v))->parse('x'));

// direct instantiation infers the class template from the wrapped type
assertType("list<array{type: 'a'}>", (new ListType($shape))->parse('x'));
assertType("array<string, array{type: 'a'}>", (new RecordType($shape))->parse('x'));
assertType("array{type: 'a'}|null", (new NullableType($shape))->parse('x'));
assertType("array{type: 'a'}", (new CatchType($shape, ['type' => 'a']))->parse('x'));
assertType("array{type: 'a'}", (new RefineType($shape, fn(array $v) => true, 'msg'))->parse('x'));
assertType("array{type: 'a'}", (new PreprocessType($shape, fn(mixed $v) => $v))->parse('x'));
assertType("array{type: 'a'}", (new PipeType(Ztan::string(), $shape))->parse('x'));
