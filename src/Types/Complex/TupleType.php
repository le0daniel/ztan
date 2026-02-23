<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Complex;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TValue
 * @extends BaseType<TValue>
 */
final readonly class TupleType extends BaseType
{
    /** @var list<Type<mixed>> */
    private array $types;

    /**
     * @param Type<mixed> ...$types
     */
    public function __construct(Type ...$types)
    {
        $this->types = array_values($types);
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if (!is_array($value)) {
            $context->addIssue(Issue::invalidType(
                "Expected a tuple.",
                $value,
            ));
            return Value::INVALID;
        }

        if (!array_is_list($value)) {
            $context->addIssue(Issue::invalidType(
                "Expected a tuple, got a non-sequential array.",
                $value,
            ));
            return Value::INVALID;
        }

        $expectedCount = count($this->types);
        $actualCount = count($value);
        if ($actualCount !== $expectedCount) {
            $context->addIssue(Issue::invalidType(
                "Expected exactly {$expectedCount} elements, got {$actualCount}.",
                $value,
            ));
            return Value::INVALID;
        }

        $hasIssues = false;
        $validated = [];
        foreach($this->types as $i => $type) {
            $context->enterPath($i);
            try {
                $validatedValue = $type->execute($value[$i], $context);
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

        // @phpstan-ignore return.type (TValue is resolved by TupleTypeConstructorResolver)
        return $validated;
    }
}
