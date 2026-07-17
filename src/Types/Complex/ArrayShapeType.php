<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Complex;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Shape;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @template TProperties of array
 * @extends BaseType<TProperties>
 */
final readonly class ArrayShapeType extends BaseType implements Shape
{
    /**
     * Expects a key-value array where the key is the property name and the value is the property type.
     * Adding a ? at the end of the property name makes it optional
     * Example:
     *
     * ```
     * [
     *     'name' => new StringType(),
     *     'age' => new IntType(),
     *     'address?' => new StringType(),
     * ]
     * // => array{name: string, age: int, address?: string}
     * ```
     *
     * @param TProperties $properties
     */
    public function __construct(
        public array $properties,
    )
    {
    }

    public function execute(mixed $value, Context $context): array|Value
    {
        if (!is_array($value)) {
            $context->addIssue(Issue::invalidType("Value is not an array.", $value));
            return Value::INVALID;
        }

        $hasIssues = false;
        $validatedValue = [];

        /** @var array<string, Type<mixed>> $properties */
        $properties = $this->properties;
        foreach ($properties as $key => $property) {
            $isOptional = str_ends_with($key, '?');
            $propertyName = $isOptional ? substr($key, 0, -1) : $key;
            $context->enterPath($propertyName);

            try {
                $valueExists = array_key_exists($propertyName, $value);

                if (!$valueExists) {
                    if ($isOptional) {
                        continue;
                    }

                    $context->addIssue(Issue::missingValue(
                        "Property {$propertyName} is required.",
                        metadata: ['property' => $propertyName],
                    ));
                    $hasIssues = true;
                    continue;
                }

                $propertyValue = $value[$propertyName] ?? null;
                $propertyResult = $property->execute($propertyValue, $context);
                if (Value::isInvalid($propertyResult)) {
                    $hasIssues = true;
                    continue;
                }

                $validatedValue[$propertyName] = $propertyResult;
            } finally {
                $context->leavePath();
            }
        }

        if ($hasIssues) {
            return Value::INVALID;
        }

        /** @var TProperties $validatedValue */
        return $validatedValue;
    }

    /**
     * @param array<string, Type<mixed>> $fields
     * @return self<array<string, Type<mixed>>>
     */
    public function extend(array $fields): self
    {
        /** @var array<string, Type<mixed>> $merged */
        $merged = [...$this->properties, ...$fields];
        return new self($merged);
    }

    /**
     * @param list<string> $keys
     * @return self<array<string, Type<mixed>>>
     */
    public function omit(array $keys): self
    {
        /** @var array<string, Type<mixed>> $filtered */
        $filtered = array_filter(
            $this->properties,
            function (string $key) use ($keys): bool {
                $cleanKey = str_ends_with($key, '?') ? substr($key, 0, -1) : $key;
                return !in_array($cleanKey, $keys, true);
            },
            ARRAY_FILTER_USE_KEY,
        );
        return new self($filtered);
    }

    /** @return Type<mixed>|null */
    private function findPropertyType(string $name): ?Type
    {
        /** @var Type<mixed>|null */
        return $this->properties[$name] ?? $this->properties[$name . '?'] ?? null;
    }

    public function executeProperty(string $propertyName, mixed $value, Context $context): mixed
    {
        if (!is_array($value)) {
            return Value::INVALID;
        }

        $type = $this->findPropertyType($propertyName);
        if ($type === null) {
            return Value::INVALID;
        }

        if (!array_key_exists($propertyName, $value)) {
            return Value::INVALID;
        }

        return $type->execute($value[$propertyName], $context);
    }
}