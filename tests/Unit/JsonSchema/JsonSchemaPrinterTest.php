<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\JsonSchema;

use DateTimeImmutable;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\JsonSchema\Exceptions\UnsupportedTypeException;
use Le0daniel\Ztan\JsonSchema\Io;
use Le0daniel\Ztan\JsonSchema\JsonSchemaPrinter;
use Le0daniel\Ztan\Types\Scalars\DateTimeStringType;
use Le0daniel\Ztan\Types\Scalars\EnumType;
use Le0daniel\Ztan\Types\Scalars\LiteralType;
use Le0daniel\Ztan\Types\Scalars\MixedType;
use Le0daniel\Ztan\Types\Scalars\NeverType;
use Le0daniel\Ztan\Types\Scalars\NullType;
use Le0daniel\Ztan\Ztan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum PrinterTestSuit {
    case HEARTS;
    case DIAMONDS;
}

enum PrinterTestStatus: string {
    case Active = 'active';
    case Inactive = 'inactive';
}

final class JsonSchemaPrinterTest extends TestCase
{
    /**
     * @return iterable<string, array{Type<mixed>, array<string, mixed>}>
     */
    public static function modeIndependentProvider(): iterable
    {
        // ── Scalars ──────────────────────────────────────────────────────────
        yield 'string' => [Ztan::string(), ['type' => 'string']];
        yield 'int' => [Ztan::int(), ['type' => 'integer']];
        yield 'float' => [Ztan::float(), ['type' => 'number']];
        yield 'bool' => [Ztan::bool(), ['type' => 'boolean']];
        yield 'null' => [new NullType(), ['type' => 'null']];
        yield 'mixed is the empty schema' => [new MixedType(), []];
        yield 'mixed with meta' => [
            new MixedType()->meta(description: 'anything'),
            ['description' => 'anything'],
        ];

        // ── Scalar literals ──────────────────────────────────────────────────
        yield 'literal string' => [Ztan::literal('a'), ['const' => 'a']];
        yield 'literal int' => [Ztan::literal(42), ['const' => 42]];
        yield 'literal float' => [Ztan::literal(1.5), ['const' => 1.5]];
        yield 'literal bool' => [Ztan::literal(true), ['const' => true]];

        // ── Containers ───────────────────────────────────────────────────────
        yield 'list' => [
            Ztan::list(Ztan::string()),
            ['type' => 'array', 'items' => ['type' => 'string']],
        ];
        yield 'record' => [
            Ztan::record(Ztan::int()),
            ['type' => 'object', 'additionalProperties' => ['type' => 'integer']],
        ];
        yield 'tuple' => [
            Ztan::tuple(Ztan::string(), Ztan::int()),
            [
                'type' => 'array',
                'prefixItems' => [['type' => 'string'], ['type' => 'integer']],
                'items' => false,
                'minItems' => 2,
            ],
        ];
        yield 'empty tuple' => [
            Ztan::tuple(),
            ['type' => 'array', 'maxItems' => 0],
        ];

        // ── Shapes ───────────────────────────────────────────────────────────
        yield 'array shape with optional key' => [
            Ztan::arrayShape(['name' => Ztan::string(), 'age?' => Ztan::int()]),
            [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string'], 'age' => ['type' => 'integer']],
                'required' => ['name'],
                'additionalProperties' => false,
            ],
        ];
        yield 'object shape with optional key' => [
            Ztan::objectShape(['name' => Ztan::string(), 'age?' => Ztan::int()]),
            [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string'], 'age' => ['type' => 'integer']],
                'required' => ['name'],
                'additionalProperties' => false,
            ],
        ];
        yield 'shape with a numeric-string key keeps required entries as strings' => [
            Ztan::arrayShape(['0' => Ztan::string()]),
            [
                'type' => 'object',
                'properties' => ['0' => ['type' => 'string']],
                'required' => ['0'],
                'additionalProperties' => false,
            ],
        ];
        yield 'empty shape omits properties and required' => [
            Ztan::arrayShape([]),
            ['type' => 'object', 'additionalProperties' => false],
        ];
        yield 'shape with only optional keys omits required' => [
            Ztan::arrayShape(['name?' => Ztan::string()]),
            [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string']],
                'additionalProperties' => false,
            ],
        ];
        yield 'deep nesting' => [
            Ztan::arrayShape(['items' => Ztan::list(Ztan::arrayShape(['id' => Ztan::int()]))]),
            [
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => ['id' => ['type' => 'integer']],
                            'required' => ['id'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['items'],
                'additionalProperties' => false,
            ],
        ];

        // ── Unions ───────────────────────────────────────────────────────────
        yield 'union' => [
            Ztan::union(Ztan::string(), Ztan::int()),
            ['anyOf' => [['type' => 'string'], ['type' => 'integer']]],
        ];
        yield 'union of literals collapses to enum' => [
            Ztan::union(Ztan::literal('png'), Ztan::literal('jpg'), Ztan::literal('webp')),
            ['enum' => ['png', 'jpg', 'webp']],
        ];
        yield 'union of literals with a described member does not collapse' => [
            Ztan::union(Ztan::literal('png')->meta(description: 'Portable Network Graphics'), Ztan::literal('jpg')),
            ['anyOf' => [['const' => 'png', 'description' => 'Portable Network Graphics'], ['const' => 'jpg']]],
        ];
        yield 'discriminated union' => [
            Ztan::discriminatedUnion('type', [
                Ztan::arrayShape(['type' => Ztan::literal('a'), 'value' => Ztan::string()]),
                Ztan::arrayShape(['type' => Ztan::literal('b'), 'count' => Ztan::int()]),
            ]),
            [
                'anyOf' => [
                    [
                        'type' => 'object',
                        'properties' => ['type' => ['const' => 'a'], 'value' => ['type' => 'string']],
                        'required' => ['type', 'value'],
                        'additionalProperties' => false,
                    ],
                    [
                        'type' => 'object',
                        'properties' => ['type' => ['const' => 'b'], 'count' => ['type' => 'integer']],
                        'required' => ['type', 'count'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];
        yield 'discriminated union members keep their descriptions' => [
            Ztan::discriminatedUnion('type', [
                Ztan::arrayShape(['type' => Ztan::literal('created')])->meta(description: 'Created event'),
                Ztan::arrayShape(['type' => Ztan::literal('deleted')])->meta(description: 'Deleted event'),
            ]),
            [
                'anyOf' => [
                    [
                        'type' => 'object',
                        'properties' => ['type' => ['const' => 'created']],
                        'required' => ['type'],
                        'additionalProperties' => false,
                        'description' => 'Created event',
                    ],
                    [
                        'type' => 'object',
                        'properties' => ['type' => ['const' => 'deleted']],
                        'required' => ['type'],
                        'additionalProperties' => false,
                        'description' => 'Deleted event',
                    ],
                ],
            ],
        ];

        // ── Nullable ─────────────────────────────────────────────────────────
        yield 'nullable string' => [
            Ztan::string()->nullable(),
            ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
        ];
        yield 'nullable shape' => [
            Ztan::arrayShape(['name' => Ztan::string()])->nullable(),
            [
                'anyOf' => [
                    [
                        'type' => 'object',
                        'properties' => ['name' => ['type' => 'string']],
                        'required' => ['name'],
                        'additionalProperties' => false,
                    ],
                    ['type' => 'null'],
                ],
            ],
        ];

        // ── Pass-through wrappers ────────────────────────────────────────────
        yield 'catch prints the inner type' => [
            Ztan::string()->catch('fallback'),
            ['type' => 'string'],
        ];
        yield 'refine prints the inner type' => [
            Ztan::string()->refine(fn (string $value): bool => true, 'message'),
            ['type' => 'string'],
        ];
        yield 'preprocess prints the inner type' => [
            Ztan::string()->preprocess(fn (mixed $value): mixed => $value),
            ['type' => 'string'],
        ];

        // ── Meta keywords ────────────────────────────────────────────────────
        yield 'meta description' => [
            Ztan::string()->meta(description: 'A description'),
            ['type' => 'string', 'description' => 'A description'],
        ];
        yield 'meta title only' => [
            Ztan::string()->meta(title: 'Only title'),
            ['type' => 'string', 'title' => 'Only title'],
        ];
        yield 'meta with all fields keeps keyword order' => [
            Ztan::string()->meta(description: 'd', title: 't', examples: ['a'], deprecated: true),
            ['type' => 'string', 'title' => 't', 'description' => 'd', 'deprecated' => true, 'examples' => ['a']],
        ];
        yield 'meta on a nested property' => [
            Ztan::arrayShape(['name' => Ztan::string()->meta(description: 'The name')]),
            [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string', 'description' => 'The name']],
                'required' => ['name'],
                'additionalProperties' => false,
            ],
        ];
        yield 'repeated meta replaces' => [
            Ztan::string()->meta(title: 'gone')->meta(description: 'kept'),
            ['type' => 'string', 'description' => 'kept'],
        ];
        yield 'meta before nullable annotates the inner branch' => [
            Ztan::string()->meta(description: 'inner')->nullable(),
            ['anyOf' => [['type' => 'string', 'description' => 'inner'], ['type' => 'null']]],
        ];
        yield 'meta after nullable annotates the top level' => [
            Ztan::string()->nullable()->meta(description: 'top'),
            ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'description' => 'top'],
        ];

        // ── Pipe ─────────────────────────────────────────────────────────────
        yield 'pipe with identical printable sides' => [
            Ztan::string()->pipe(Ztan::string()->minLength(3)),
            ['type' => 'string'],
        ];
    }

    /**
     * @param Type<mixed> $schema
     * @param array<string, mixed> $expected
     */
    #[DataProvider('modeIndependentProvider')]
    public function testModeIndependentPrinting(Type $schema, array $expected): void
    {
        self::assertSame($expected, new JsonSchemaPrinter(Io::Input)->printToArray($schema));
        self::assertSame($expected, new JsonSchemaPrinter(Io::Output)->printToArray($schema));
    }

    /**
     * @return iterable<string, array{Type<mixed>, array<string, mixed>, array<string, mixed>}>
     */
    public static function modeDivergentProvider(): iterable
    {
        yield 'pipe prints its input side in input mode and its output side in output mode' => [
            Ztan::string()
                ->transform(fn (string $value): mixed => json_decode($value, true))
                ->pipe(Ztan::arrayShape(['name' => Ztan::string()])),
            ['type' => 'string'],
            [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string']],
                'required' => ['name'],
                'additionalProperties' => false,
            ],
        ];
        yield 'chained pipes print the outermost ends' => [
            Ztan::string()->pipe(Ztan::int())->pipe(Ztan::float()),
            ['type' => 'string'],
            ['type' => 'number'],
        ];
        yield 'meta before pipe annotates the input side only' => [
            Ztan::string()->meta(description: 'raw')->pipe(Ztan::int()),
            ['type' => 'string', 'description' => 'raw'],
            ['type' => 'integer'],
        ];
        yield 'meta after pipe annotates the top level in both modes' => [
            Ztan::string()->pipe(Ztan::int())->meta(description: 'top'),
            ['type' => 'string', 'description' => 'top'],
            ['type' => 'integer', 'description' => 'top'],
        ];
    }

    /**
     * @param Type<mixed> $schema
     * @param array<string, mixed> $expectedInput
     * @param array<string, mixed> $expectedOutput
     */
    #[DataProvider('modeDivergentProvider')]
    public function testModeDivergentPrinting(Type $schema, array $expectedInput, array $expectedOutput): void
    {
        self::assertSame($expectedInput, new JsonSchemaPrinter(Io::Input)->printToArray($schema));
        self::assertSame($expectedOutput, new JsonSchemaPrinter(Io::Output)->printToArray($schema));
    }

    /**
     * @return iterable<string, array{Type<mixed>, array<string, mixed>}>
     */
    public static function printsOnInputButThrowsOnOutputProvider(): iterable
    {
        yield 'date time string' => [
            Ztan::dateTimeString('Y-m-d'),
            ['type' => 'string'],
        ];
        yield 'coerced pure enum literal prints the case name' => [
            new LiteralType(PrinterTestSuit::HEARTS, coerce: true),
            ['const' => 'HEARTS'],
        ];
        yield 'coerced backed enum literal prints the case name, not the value' => [
            new LiteralType(PrinterTestStatus::Active, coerce: true),
            ['const' => 'Active'],
        ];
        yield 'coerced pure enum prints the case names' => [
            new EnumType(PrinterTestSuit::class, coerce: true),
            ['enum' => ['HEARTS', 'DIAMONDS']],
        ];
        yield 'coerced backed enum prints the case names' => [
            new EnumType(PrinterTestStatus::class, coerce: true),
            ['enum' => ['Active', 'Inactive']],
        ];
        yield 'transform prints its input side' => [
            Ztan::string()->transform(fn (string $value): int => strlen($value)),
            ['type' => 'string'],
        ];
        yield 'pipe whose output side is a transform' => [
            Ztan::string()->pipe(Ztan::string()->transform(fn (string $value): int => strlen($value))),
            ['type' => 'string'],
        ];
    }

    /**
     * @param Type<mixed> $schema
     * @param array<string, mixed> $expectedInput
     */
    #[DataProvider('printsOnInputButThrowsOnOutputProvider')]
    public function testPrintsOnInputButThrowsOnOutput(Type $schema, array $expectedInput): void
    {
        self::assertSame($expectedInput, new JsonSchemaPrinter(Io::Input)->printToArray($schema));

        $this->expectException(UnsupportedTypeException::class);
        new JsonSchemaPrinter(Io::Output)->printToArray($schema);
    }

    /**
     * @return iterable<string, array{Type<mixed>}>
     */
    public static function throwsInBothModesProvider(): iterable
    {
        yield 'uncoerced enum literal only accepts the instance' => [new LiteralType(PrinterTestSuit::HEARTS)];
        yield 'uncoerced enum only accepts instances' => [new EnumType(PrinterTestSuit::class)];
        yield 'instance' => [Ztan::instance(DateTimeImmutable::class)];
        yield 'never' => [new NeverType()];
        yield 'empty union' => [Ztan::union()];
        yield 'empty discriminated union' => [Ztan::discriminatedUnion('type', [])];
        yield 'unknown third-party type' => [
            new class implements Type {
                public function execute(mixed $value, Context $context): mixed
                {
                    return $value;
                }
            },
        ];
    }

    /**
     * @param Type<mixed> $schema
     */
    #[DataProvider('throwsInBothModesProvider')]
    public function testThrowsInBothModes(Type $schema): void
    {
        try {
            new JsonSchemaPrinter(Io::Input)->printToArray($schema);
            self::fail('Expected UnsupportedTypeException in Input mode.');
        } catch (UnsupportedTypeException) {
        }

        $this->expectException(UnsupportedTypeException::class);
        new JsonSchemaPrinter(Io::Output)->printToArray($schema);
    }

    public function testPipeWithAnUnprintableInputSidePrintsOnlyInOutputMode(): void
    {
        $schema = Ztan::instance(DateTimeImmutable::class)->pipe(Ztan::string());

        self::assertSame(['type' => 'string'], new JsonSchemaPrinter(Io::Output)->printToArray($schema));

        $this->expectException(UnsupportedTypeException::class);
        new JsonSchemaPrinter(Io::Input)->printToArray($schema);
    }

    public function testExceptionMessageNamesTheTypeAndTheMode(): void
    {
        try {
            new JsonSchemaPrinter(Io::Output)->printToArray(Ztan::dateTimeString('Y-m-d'));
            self::fail('Expected UnsupportedTypeException.');
        } catch (UnsupportedTypeException $exception) {
            self::assertStringContainsString(DateTimeStringType::class, $exception->getMessage());
            self::assertStringContainsString('Output', $exception->getMessage());
        }
    }

    public function testAdditionalPropertiesOptionOmitsTheKeywordOnShapes(): void
    {
        $schema = Ztan::arrayShape([
            'name' => Ztan::string(),
            'child' => Ztan::arrayShape(['id' => Ztan::int()]),
        ]);

        self::assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'child' => [
                        'type' => 'object',
                        'properties' => ['id' => ['type' => 'integer']],
                        'required' => ['id'],
                    ],
                ],
                'required' => ['name', 'child'],
            ],
            new JsonSchemaPrinter(additionalProperties: true)->printToArray($schema),
        );
    }

    public function testRecordIsUnaffectedByTheAdditionalPropertiesOption(): void
    {
        $schema = Ztan::record(Ztan::string());
        $expected = ['type' => 'object', 'additionalProperties' => ['type' => 'string']];

        self::assertSame($expected, new JsonSchemaPrinter(additionalProperties: false)->printToArray($schema));
        self::assertSame($expected, new JsonSchemaPrinter(additionalProperties: true)->printToArray($schema));
    }

    public function testJsonEncodingOfANestedSchema(): void
    {
        $schema = Ztan::arrayShape([
            'tags' => Ztan::tuple(Ztan::string(), Ztan::int()),
        ]);

        self::assertSame(
            '{"type":"object","properties":{"tags":{"type":"array","prefixItems":[{"type":"string"},{"type":"integer"}],"items":false,"minItems":2}},"required":["tags"],"additionalProperties":false}',
            json_encode(new JsonSchemaPrinter()->printToArray($schema)),
        );
    }
}
