<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Complex;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TValue
 * @extends BaseType<list<TValue>>
 */
final readonly class ListType extends BaseType
{
    /**
     * @param Type<TValue> $type
     */
    public function __construct(
        private Type $type
    )
    {
    }

    public function execute(mixed $value, Context $context): array|Value
    {
        if (!is_iterable($value)) {
            $context->addIssue(Issue::invalidType(
                "Expected an iterable list.",
                $value,
            ));
            return Value::INVALID;
        }

        $hasIssues = false;
        $validated = [];
        $expectedIndex = 0;
        foreach ($value as $index => $item) {
            if ($index !== $expectedIndex) {
                $context->addIssue(Issue::invalidType(
                    "Expected a list, got non-sequential keys.",
                    $value,
                ));
                return Value::INVALID;
            }

            $context->enterPath($index);
            try {
                $validatedValue = $this->type->execute($item, $context);
                if (Value::isInvalid($validatedValue)) {
                    $hasIssues = true;
                } else {
                    $validated[] = $validatedValue;
                }
            } finally {
                $context->leavePath();
                $expectedIndex++;
            }
        }

        if ($hasIssues) {
            return Value::INVALID;
        }

        return $validated;
    }
}
