<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @extends BaseType<bool>
 */
final readonly class BoolType extends BaseType
{
    /**
     * @param list<Pipe<bool>> $pipeline
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
            $value === 1, $value === 1.0, $value === 'true' => true,
            $value === 0, $value === 0.0, $value === 'false' => false,
            default => $value,
        };
    }

    public function execute(mixed $value, Context $context): bool|Value
    {
        if ($this->coerce) {
            $value = self::coerceValue($value);
        }

        if (!is_bool($value)) {
            $context->addIssue(new Issue("Expected boolean."));
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
