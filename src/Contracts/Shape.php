<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Contracts;

interface Shape
{
    /**
     * Extracts a named property from the raw input data, validates it, and returns the result.
     * The shape handles its own data format (array access for ArrayShape, property access for future ObjectShape).
     * Returns Value::INVALID if: wrong data type, property doesn't exist, or validation fails.
     * @return mixed The validated property value or Value::INVALID
     */
    public function executeProperty(string $propertyName, mixed $value, Context $context): mixed;
}
