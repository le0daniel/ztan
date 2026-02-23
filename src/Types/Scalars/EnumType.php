<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use UnitEnum;

/**
 * @template T of UnitEnum
 * @extends BaseType<T>
 */
final readonly class EnumType extends BaseType
{
    /**
     * @param class-string<T> $enumClass
     * @param list<Pipe<T>> $pipeline
     */
    public function __construct(
        private string $enumClass,
        private array $pipeline = [],
        private bool $coerce = false,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if ($this->coerce && is_string($value)) {
            $value = $this->coerceFromName($value);
        }

        if (!$value instanceof $this->enumClass) {
            $context->addIssue(new Issue("Invalid value."));
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

    /**
     * @return T|string
     */
    private function coerceFromName(string $name): UnitEnum|string
    {
        foreach ($this->enumClass::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return $name;
    }
}
