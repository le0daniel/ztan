<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Scalars;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Integers\GreaterThan;
use Le0daniel\Ztan\Types\Pipe\Integers\LowerThan;
use Le0daniel\Ztan\Types\Pipe\Integers\MultipleOf;

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
        return $this->withPipe(new GreaterThan($threshold));
    }

    public function gte(int $threshold): self
    {
        return $this->withPipe(new GreaterThan($threshold, including: true));
    }

    public function lt(int $threshold): self
    {
        return $this->withPipe(new LowerThan($threshold));
    }

    public function lte(int $threshold): self
    {
        return $this->withPipe(new LowerThan($threshold, including: true));
    }

    public function range(int $min, int $max, bool $including = true): self
    {
        return $this
            ->withPipe(new GreaterThan($min, including: $including))
            ->withPipe(new LowerThan($max, including: $including));
    }

    public function positive(): self
    {
        return $this->withPipe(new GreaterThan(0));
    }

    public function negative(): self
    {
        return $this->withPipe(new LowerThan(0));
    }

    public function nonnegative(): self
    {
        return $this->withPipe(new GreaterThan(0, including: true));
    }

    public function nonpositive(): self
    {
        return $this->withPipe(new LowerThan(0, including: true));
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
            is_string($value) && filter_var($value, FILTER_VALIDATE_INT) !== false => (int) $value,
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
