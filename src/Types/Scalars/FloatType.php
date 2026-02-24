<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Floats\GreaterThan;
use Le0daniel\Assertions\Types\Pipe\Floats\LowerThan;

/**
 * @extends BaseType<float>
 */
final readonly class FloatType extends BaseType
{
    /**
     * @param list<Pipe<float>> $pipeline
     */
    public function __construct(
        private array $pipeline = [],
        private bool $coerce = false
    )
    {
    }

    /**
     * @param Pipe<float> $pipe
     */
    private function withPipe(Pipe $pipe): self
    {
        return new self([...$this->pipeline, $pipe], $this->coerce);
    }

    public function gt(float $threshold): self
    {
        return $this->withPipe(new GreaterThan($threshold));
    }

    public function gte(float $threshold): self
    {
        return $this->withPipe(new GreaterThan($threshold, including: true));
    }

    public function lt(float $threshold): self
    {
        return $this->withPipe(new LowerThan($threshold));
    }

    public function lte(float $threshold): self
    {
        return $this->withPipe(new LowerThan($threshold, including: true));
    }

    public function range(float $min, float $max, bool $including = true): self
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

    public static function coerceValue(mixed $value): mixed
    {
        return match (true) {
            is_float($value) => $value,
            is_int($value) => (float) $value,
            is_bool($value) => $value ? 1.0 : 0.0,
            is_string($value) && is_numeric($value) => (float) $value,
            default => $value,
        };
    }

    public function execute(mixed $value, Context $context): float|Value
    {
        $value = $this->coerce ? self::coerceValue($value) : $value;

        if (!is_float($value)) {
            $context->addIssue(Issue::invalidType("Expected float.", $value));
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
