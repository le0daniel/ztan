<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\PHPStan\Cases;

use Le0daniel\Ztan\Ztan;
use function PHPStan\Testing\assertType;

// pipe() wraps the receiver's output and the target's output
assertType('Le0daniel\Ztan\Types\PipeType<string, int>', Ztan::string()->pipe(Ztan::int()));
assertType('int', Ztan::string()->pipe(Ztan::int())->parse('1'));

// the canonical example: the piped type wins over the transform's output
// (json_decode with associative: true never yields objects, hence mixed~object)
$schema = Ztan::string()
    ->transform(fn (string $value): mixed => json_decode($value, true, JSON_THROW_ON_ERROR))
    ->pipe(Ztan::arrayShape(['name' => Ztan::string(), 'age' => Ztan::int()]));
assertType('Le0daniel\Ztan\Types\PipeType<mixed~object, array{name: string, age: int}>', $schema);
assertType('array{name: string, age: int}', $schema->parse('{"name":"leo","age":30}'));
assertType('Le0daniel\Ztan\Data\ParseError|Le0daniel\Ztan\Data\ParseSuccess<array{name: string, age: int}>', $schema->safeParse('x'));

// piping straight into a shape binds the shape's properties
$shapePipe = Ztan::string()->pipe(Ztan::arrayShape(['id' => Ztan::int()]));
assertType('array{id: int}', $shapePipe->parse('x'));

// chained pipes keep the last type's output
assertType('float', Ztan::string()->pipe(Ztan::int())->pipe(Ztan::float())->parse('x'));

// composition with other wrappers
assertType('Le0daniel\Ztan\Types\NullableType<array{id: int}>', $shapePipe->nullable());
assertType('array{id: int}|null', $shapePipe->nullable()->parse('x'));

// generics survive a meta() hop
assertType('array{id: int}', $shapePipe->meta(description: 'x')->parse('x'));
