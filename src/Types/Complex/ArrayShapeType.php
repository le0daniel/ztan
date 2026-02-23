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
            $context->addIssue(new Issue('Value is not an array.'));
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

                    $context->addIssue(new Issue(
                        "Property {$propertyName} does not exist}",
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