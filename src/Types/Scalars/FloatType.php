<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

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
