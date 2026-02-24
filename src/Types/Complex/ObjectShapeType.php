<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Complex;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Shape;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TProperties
 * @extends BaseType<TProperties>
 */
final readonly class ObjectShapeType extends BaseType implements Shape
{
    /** @var array<string, Type<mixed>> */
    private array $properties;

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
     * // => object{name: string, age: int, address?: string}
     * ```
     *
     * @param array<string, Type<mixed>> $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if (!is_object($value)) {
            $context->addIssue(Issue::invalidType("Value is not an object.", $value));
            return Value::INVALID;
        }

        $hasIssues = false;
        $validatedValue = new \stdClass();

        foreach ($this->properties as $key => $property) {
            $isOptional = str_ends_with($key, '?');
            $propertyName = $isOptional ? substr($key, 0, -1) : $key;
            $context->enterPath($propertyName);

            try {
                $valueExists = property_exists($value, $propertyName) || (method_exists($value, '__isset') && $value->__isset($propertyName));

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

                $propertyValue = $value->{$propertyName}; // @phpstan-ignore property.dynamicName
                $propertyResult = $property->execute($propertyValue, $context);
                if (Value::isInvalid($propertyResult)) {
                    $hasIssues = true;
                    continue;
                }

                $validatedValue->{$propertyName} = $propertyResult;
            } finally {
                $context->leavePath();
            }
        }

        if ($hasIssues) {
            return Value::INVALID;
        }

        // @phpstan-ignore return.type (TProperties is resolved by ObjectShapeTypeConstructorResolver)
        return $validatedValue;
    }

    /** @return Type<mixed>|null */
    private function findPropertyType(string $name): ?Type
    {
        return $this->properties[$name] ?? $this->properties[$name . '?'] ?? null;
    }

    public function executeProperty(string $propertyName, mixed $value, Context $context): mixed
    {
        if (!is_object($value)) {
            return Value::INVALID;
        }

        $type = $this->findPropertyType($propertyName);
        if ($type === null) {
            return Value::INVALID;
        }

        $propertyExists = property_exists($value, $propertyName) || (method_exists($value, '__isset') && $value->__isset($propertyName));
        if (!$propertyExists) {
            return Value::INVALID;
        }

        return $type->execute($value->{$propertyName}, $context); // @phpstan-ignore property.dynamicName
    }
}
