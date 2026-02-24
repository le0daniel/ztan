<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Complex;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Records\MaxRecords;
use Le0daniel\Ztan\Types\Pipe\Records\MinRecords;

/**
 * @template TValueType
 * @extends BaseType<array<string, TValueType>>
 */
final readonly class RecordType extends BaseType
{
    /**
     * @param Type<TValueType> $valueType
     * @param list<MinRecords|MaxRecords> $pipeline
     */
    public function __construct(
        private Type $valueType,
        private array $pipeline = [],
    )
    {
    }

    /** @return self<TValueType> */
    public function minProperties(int $count, bool $including = true): self
    {
        return new self($this->valueType, [...$this->pipeline, new MinRecords($count, $including)]);
    }

    /** @return self<TValueType> */
    public function maxProperties(int $count, bool $including = true): self
    {
        return new self($this->valueType, [...$this->pipeline, new MaxRecords($count, $including)]);
    }

    /** @return self<TValueType> */
    public function nonEmpty(): self
    {
        return new self($this->valueType, [...$this->pipeline, new MinRecords(1)]);
    }

    public function execute(mixed $value, Context $context): array|Value
    {
        if (!is_iterable($value)) {
            $context->addIssue(Issue::invalidType(
                "Expected an iterable record.",
                $value,
            ));
            return Value::INVALID;
        }

        $hasIssues = false;
        $validated = [];
        foreach ($value as $key => $itemValue) {
            if (!is_string($key)) {
                $context->addIssue(Issue::invalidType(
                    "Record key must be a string.",
                    $key,
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

        foreach ($this->pipeline as $pipe) {
            $validated = $pipe->execute($validated, $context);
            if (Value::isInvalid($validated)) {
                return $validated;
            }
        }

        return $validated;
    }
}
