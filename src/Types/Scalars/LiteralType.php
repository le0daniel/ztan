<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use UnitEnum;

/**
 * @template T of string|int|float|bool|UnitEnum
 * @extends BaseType<T>
 */
final readonly class LiteralType extends BaseType
{
    /**
     * @param T $literal
     * @param list<Pipe<T>> $pipeline
     */
    public function __construct(
        private string|int|float|bool|UnitEnum $literal,
        private array $pipeline = [],
        private bool $coerce = false,
    ) {
    }

    /**
     * @return T|Value::INVALID
     */
    public function execute(mixed $value, Context $context): mixed
    {
        if ($this->coerce) {
            $value = match (true) {
                is_string($this->literal) => StringType::coerceValue($value),
                is_int($this->literal) => IntType::coerceValue($value),
                is_float($this->literal) => FloatType::coerceValue($value),
                is_bool($this->literal) => BoolType::coerceValue($value),
                $this->literal instanceof UnitEnum => EnumType::coerceValue($this->literal::class, $value),
            };
        }

        if ($value !== $this->literal) {
            $context->addIssue(Issue::invalidValue("Invalid value.", $value, metadata: ['expected' => $this->literal]));
            return Value::INVALID;
        }

        foreach ($this->pipeline as $pipe) {
            $value = $pipe->execute($value, $context);
            if (Value::isInvalid($value)) {
                return Value::INVALID;
            }
        }

        return $value;
    }
}
