<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Complex;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Lists\MaxItems;
use Le0daniel\Ztan\Types\Pipe\Lists\MinItems;

/**
 * @template TValue
 * @extends BaseType<list<TValue>>
 */
final readonly class ListType extends BaseType
{
    /**
     * @param Type<TValue> $type
     * @param list<MinItems|MaxItems> $pipeline
     */
    public function __construct(
        private Type $type,
        private array $pipeline = [],
    )
    {
    }

    /** @return self<TValue> */
    public function minItems(int $count, bool $including = true): self
    {
        return new self($this->type, [...$this->pipeline, new MinItems($count, $including)]);
    }

    /** @return self<TValue> */
    public function maxItems(int $count, bool $including = true): self
    {
        return new self($this->type, [...$this->pipeline, new MaxItems($count, $including)]);
    }

    /** @return self<TValue> */
    public function nonEmpty(): self
    {
        return new self($this->type, [...$this->pipeline, new MinItems(1)]);
    }

    /** @return self<TValue> */
    public function length(int $count): self
    {
        return new self($this->type, [...$this->pipeline, new MinItems($count), new MaxItems($count)]);
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

        foreach ($this->pipeline as $pipe) {
            $validated = $pipe->execute($validated, $context);
            if (Value::isInvalid($validated)) {
                return $validated;
            }
        }

        return $validated;
    }
}
