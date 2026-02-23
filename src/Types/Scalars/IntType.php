<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

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
            $context->addIssue(new Issue("Expected integer."));
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
