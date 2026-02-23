<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TValue
 * @extends BaseType<TValue>
 */
final readonly class RefineType extends BaseType
{
    /**
     * @param Type<TValue> $type
     * @param \Closure(TValue): bool $refiner
     * @param string $message
     */
    public function __construct(
        private Type $type,
        private \Closure $refiner,
        private string $message
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        $value = $this->type->execute($value, $context);
        if (Value::isInvalid($value)) {
            return Value::INVALID;
        }

        if (!($this->refiner)($value)) {
            $context->addIssue(new Issue($this->message));
            return Value::INVALID;
        }

        return $value;
    }
}