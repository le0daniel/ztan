<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

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
        public Type $type,
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
            $context->addIssue(Issue::custom($this->message, $value));
            return Value::INVALID;
        }

        return $value;
    }
}