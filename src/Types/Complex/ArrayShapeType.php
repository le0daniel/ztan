<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Complex;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TProperties of array
 * @extends BaseType<TProperties>
 */
final readonly class ArrayShapeType extends BaseType
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
        private array $properties,
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
}