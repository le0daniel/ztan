<?php declare(strict_types=1);

namespace Le0daniel\Ztan\JsonSchema;

use Le0daniel\Ztan\Contracts\HasMetadata;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Meta;
use Le0daniel\Ztan\JsonSchema\Exceptions\UnsupportedTypeException;
use Le0daniel\Ztan\Types\CatchType;
use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\DiscriminatedUnionType;
use Le0daniel\Ztan\Types\Complex\ListType;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\Complex\TupleType;
use Le0daniel\Ztan\Types\Complex\UnionType;
use Le0daniel\Ztan\Types\NullableType;
use Le0daniel\Ztan\Types\PipeType;
use Le0daniel\Ztan\Types\PreprocessType;
use Le0daniel\Ztan\Types\RefineType;
use Le0daniel\Ztan\Types\Scalars\BoolType;
use Le0daniel\Ztan\Types\Scalars\DateTimeStringType;
use Le0daniel\Ztan\Types\Scalars\EnumType;
use Le0daniel\Ztan\Types\Scalars\FloatType;
use Le0daniel\Ztan\Types\Scalars\IntType;
use Le0daniel\Ztan\Types\Scalars\LiteralType;
use Le0daniel\Ztan\Types\Scalars\MixedType;
use Le0daniel\Ztan\Types\Scalars\NullType;
use Le0daniel\Ztan\Types\Scalars\StringType;
use Le0daniel\Ztan\Types\TransformType;
use UnitEnum;

/**
 * Prints a type as a JSON schema (draft 2020-12) array. First iteration: types
 * only — validation rules (min/max/pattern/format) are not printed. The Io mode
 * selects which side of validation the schema describes; types whose given side
 * is not representable in JSON throw an UnsupportedTypeException.
 */
final readonly class JsonSchemaPrinter
{
    public function __construct(
        private Io $io = Io::Input,
        private bool $additionalProperties = false,
    )
    {
    }

    /**
     * @param Type<mixed> $type
     * @return array<string, mixed>
     * @throws UnsupportedTypeException
     */
    public function printToArray(Type $type): array
    {
        return $this->printType($type);
    }

    /**
     * @param Type<mixed> $type
     * @return array<string, mixed>
     */
    private function printType(Type $type): array
    {
        $schema = $this->printNode($type);

        if ($type instanceof HasMetadata && ($meta = $type->getMeta()) !== null) {
            return [...$schema, ...$this->metaKeywords($meta)];
        }

        return $schema;
    }

    /**
     * @param Type<mixed> $type
     * @return array<string, mixed>
     */
    private function printNode(Type $type): array
    {
        if ($type instanceof ArrayShapeType || $type instanceof ObjectShapeType) {
            /** @var array<string, Type<mixed>> $properties */
            $properties = $type->properties;
            return $this->printShape($properties);
        }

        return match (true) {
            $type instanceof StringType => ['type' => 'string'],
            $type instanceof IntType => ['type' => 'integer'],
            $type instanceof FloatType => ['type' => 'number'],
            $type instanceof BoolType => ['type' => 'boolean'],
            $type instanceof NullType => ['type' => 'null'],
            $type instanceof MixedType => [],
            $type instanceof DateTimeStringType => $this->io === Io::Input
                ? ['type' => 'string']
                : throw UnsupportedTypeException::forType($type, $this->io),
            $type instanceof LiteralType => $this->printLiteral($type),
            $type instanceof EnumType => $this->printEnum($type),
            $type instanceof ListType => ['type' => 'array', 'items' => $this->printType($type->type)],
            $type instanceof TupleType => $this->printTuple($type),
            $type instanceof RecordType => ['type' => 'object', 'additionalProperties' => $this->printType($type->valueType)],
            $type instanceof UnionType => $this->printUnion($type),
            $type instanceof DiscriminatedUnionType => $this->printDiscriminatedUnion($type),
            $type instanceof NullableType => ['anyOf' => [$this->printType($type->assertion), ['type' => 'null']]],
            $type instanceof CatchType => $this->printType($type->assertion),
            $type instanceof RefineType => $this->printType($type->type),
            $type instanceof PreprocessType => $this->printType($type->assertion),
            $type instanceof TransformType => $this->io === Io::Input
                ? $this->printType($type->assertion)
                : throw UnsupportedTypeException::forType($type, $this->io),
            $type instanceof PipeType => $this->printType(
                $this->io === Io::Input ? $type->firstType : $type->secondType,
            ),
            default => throw UnsupportedTypeException::forType($type, $this->io),
        };
    }

    /**
     * PHP silently converts numeric-string array keys ('0') to integers, so
     * keys arrive as array-key and are cast back to string for `required`.
     *
     * @param array<array-key, Type<mixed>> $properties
     * @return array<string, mixed>
     */
    private function printShape(array $properties): array
    {
        $printedProperties = [];
        $required = [];

        foreach ($properties as $key => $propertyType) {
            $key = (string) $key;
            $isOptional = str_ends_with($key, '?');
            $propertyName = $isOptional ? substr($key, 0, -1) : $key;

            $printedProperties[$propertyName] = $this->printType($propertyType);
            if (!$isOptional) {
                $required[] = $propertyName;
            }
        }

        $schema = ['type' => 'object'];
        if ($printedProperties !== []) {
            $schema['properties'] = $printedProperties;
        }
        if ($required !== []) {
            $schema['required'] = $required;
        }
        if (!$this->additionalProperties) {
            $schema['additionalProperties'] = false;
        }

        return $schema;
    }

    /**
     * @param LiteralType<string|int|float|bool|UnitEnum> $type
     * @return array<string, mixed>
     */
    private function printLiteral(LiteralType $type): array
    {
        $literal = $type->literal;
        if (!$literal instanceof UnitEnum) {
            return ['const' => $literal];
        }

        // A JSON payload can never contain an enum instance: only the coerced
        // input side (accepting the case name) is representable.
        if ($this->io === Io::Output || !$type->coerce) {
            throw UnsupportedTypeException::forType($type, $this->io);
        }

        return ['const' => $literal->name];
    }

    /**
     * @param EnumType<UnitEnum> $type
     * @return array<string, mixed>
     */
    private function printEnum(EnumType $type): array
    {
        if ($this->io === Io::Output || !$type->coerce) {
            throw UnsupportedTypeException::forType($type, $this->io);
        }

        $names = [];
        foreach ($type->enumClass::cases() as $case) {
            $names[] = $case->name;
        }

        return ['enum' => $names];
    }

    /**
     * @param TupleType<mixed> $type
     * @return array<string, mixed>
     */
    private function printTuple(TupleType $type): array
    {
        if ($type->types === []) {
            // `prefixItems` must be non-empty per the 2020-12 meta-schema.
            return ['type' => 'array', 'maxItems' => 0];
        }

        return [
            'type' => 'array',
            'prefixItems' => array_map($this->printType(...), $type->types),
            'items' => false,
            'minItems' => count($type->types),
        ];
    }

    /**
     * @param UnionType<mixed> $type
     * @return array<string, mixed>
     */
    private function printUnion(UnionType $type): array
    {
        if ($type->types === []) {
            throw UnsupportedTypeException::forType($type, $this->io);
        }

        $literalValues = $this->collectPlainLiteralValues($type->types);
        if ($literalValues !== null) {
            return ['enum' => $literalValues];
        }

        return ['anyOf' => array_map($this->printType(...), $type->types)];
    }

    /**
     * Collapses a union of undescribed literals into a single `enum`. Returns
     * null when any member is not a literal, carries meta, or is an enum-case
     * literal that would not print as a const in the current mode.
     *
     * @param list<Type<mixed>> $types
     * @return list<mixed>|null
     */
    private function collectPlainLiteralValues(array $types): ?array
    {
        $values = [];
        foreach ($types as $member) {
            if (!$member instanceof LiteralType || $member->getMeta() !== null) {
                return null;
            }

            $literal = $member->literal;
            if (!$literal instanceof UnitEnum) {
                $values[] = $literal;
                continue;
            }

            if ($this->io === Io::Output || !$member->coerce) {
                return null;
            }
            $values[] = $literal->name;
        }

        return $values;
    }

    /**
     * @param DiscriminatedUnionType<mixed> $type
     * @return array<string, mixed>
     */
    private function printDiscriminatedUnion(DiscriminatedUnionType $type): array
    {
        if ($type->shapes === []) {
            throw UnsupportedTypeException::forType($type, $this->io);
        }

        return ['anyOf' => array_map($this->printType(...), $type->shapes)];
    }

    /**
     * @return array<string, mixed>
     */
    private function metaKeywords(Meta $meta): array
    {
        $keywords = [];
        if ($meta->title !== null) {
            $keywords['title'] = $meta->title;
        }
        if ($meta->description !== null) {
            $keywords['description'] = $meta->description;
        }
        if ($meta->deprecated !== null) {
            $keywords['deprecated'] = $meta->deprecated;
        }
        if ($meta->examples !== null) {
            $keywords['examples'] = $meta->examples;
        }

        return $keywords;
    }
}
