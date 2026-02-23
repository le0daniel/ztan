<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Complex;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TValueType
 * @extends BaseType<array<string, TValueType>>
 */
final readonly class RecordType extends BaseType
{
    /**
     * @param Type<TValueType> $valueType
     */
    public function __construct(
        private Type $valueType
    )
    {
    }


    public function execute(mixed $value, Context $context): array|Value
    {
        if (!is_array($value)) {
            $context->addIssue(new Issue(
                "Expected array",
            ));
            return Value::INVALID;
        }

        $hasIssues = false;
        $validated = [];
        foreach ($value as $key => $itemValue) {
            if (!is_string($key)) {
                $context->addIssue(new Issue(
                    "Record key must be a string",
                ));
                $hasIssues = true;
                continue;
            }

            $context->enterPath($key);
            try {
                $validatedValue = $this->valueType->execute($itemValue, $context);
                if (Value::isInvalid($validatedValue)) {
                    $hasIssues = true;
                    continue;
                }

                $validated[$key] = $validatedValue;
            } finally {
                $context->leavePath();
            }
        }

        if ($hasIssues) {
            return Value::INVALID;
        }

        return $validated;
    }
}