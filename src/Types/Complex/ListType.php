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
        if (!is_array($value)) {
            $context->addIssue(Issue::invalidType(
                "Expected a list.",
                $value,
            ));
            return Value::INVALID;
        }

        if (!array_is_list($value)) {
            $context->addIssue(Issue::invalidType(
                "Expected a list, got a non-sequential array.",
                $value,
            ));
            return Value::INVALID;
        }

        $hasIssues = false;
        $validated = [];
        foreach ($value as $index => $item) {
            $context->enterPath($index);
            try {
                $validatedValue = $this->type->execute($item, $context);
                if (Value::isInvalid($validatedValue)) {
                    $hasIssues = true;
                    continue;
                }

                $validated[] = $validatedValue;
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
