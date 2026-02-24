<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Integers\Gt;
use Le0daniel\Assertions\Types\Pipe\Integers\Gte;
use Le0daniel\Assertions\Types\Pipe\Integers\Lt;
use Le0daniel\Assertions\Types\Pipe\Integers\Lte;
use Le0daniel\Assertions\Types\Pipe\Integers\MultipleOf;
use Le0daniel\Assertions\Types\Pipe\Integers\Negative;
use Le0daniel\Assertions\Types\Pipe\Integers\Positive;
use Le0daniel\Assertions\Types\Pipe\Integers\Range;

/**
 * @extends BaseType<int>
 */
final readonly class IntType extends BaseType
{
    /**
     * @param list<Pipe<int>> $pipeline
     */
    public function __construct(
        private array $pipeline = [],
        private bool $coerce = false
    )
    {
    }

    /**
     * @param Pipe<int> $pipe
     */
    private function withPipe(Pipe $pipe): self
    {
        return new self([...$this->pipeline, $pipe], $this->coerce);
    }

    public function gt(int $threshold): self
    {
        return $this->withPipe(new Gt($threshold));
    }

    public function gte(int $threshold): self
    {
        return $this->withPipe(new Gte($threshold));
    }

    public function lt(int $threshold): self
    {
        return $this->withPipe(new Lt($threshold));
    }

    public function lte(int $threshold): self
    {
        return $this->withPipe(new Lte($threshold));
    }

    public function range(int $min, int $max, bool $including = true): self
    {
        return $this->withPipe(new Range($min, $max, $including));
    }

    public function positive(): self
    {
        return $this->withPipe(new Positive());
    }

    public function negative(): self
    {
        return $this->withPipe(new Negative());
    }

    public function multipleOf(int $divisor): self
    {
        return $this->withPipe(new MultipleOf($divisor));
    }

    public static function coerceValue(mixed $value): mixed
    {
        return match (true) {
            is_int($value) => $value,
            is_float($value) => (int) $value,
            is_bool($value) => $value ? 1 : 0,
            is_string($value) && is_numeric($value) => (int) $value,
            default => $value,
        };
    }

    public function execute(mixed $value, Context $context): int|Value
    {
        $value = $this->coerce ? self::coerceValue($value) : $value;

        if (!is_int($value)) {
            $context->addIssue(Issue::invalidType("Expected integer.", $value));
            return Value::INVALID;
        }

        foreach ($this->pipeline as $pipe) {
            $value = $pipe->execute($value, $context);
            if (Value::isInvalid($value)) {
                return $value;
            }
        }

        return $value;
    }
}
