<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Complex;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TProperties of array<string, Type>
 * @implements Type<TProperties>
 */
final readonly class ArrayStructType implements Type
{
    /**
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
            return Value::INVALID;
        }

        $validatedValue = [];

        foreach ($this->properties as $key => $property) {
            $isOptional = str_ends_with($key, '?');
            $propertyName = $isOptional ? substr($key, 0, -1) : $key;

            $valueExists = array_key_exists($propertyName, $value);

            if (!$valueExists) {
                if ($isOptional) {
                    continue;
                }

                // ToDo: Add Issue
                $context->addIssue(new Issue(
                    "Property {$propertyName} does not exist}",
                ));
                return Value::INVALID;
            }

            $propertyValue = $value[$propertyName] ?? null;

            // ToDo: Enter path for better visibility.
            $propertyResult = $property->execute($propertyValue, $context);
            if (Value::isInvalid($propertyResult)) {
                return Value::INVALID;
            }

            $validatedValue[$propertyName] = $propertyResult;
        }

        return $validatedValue;
    }
}