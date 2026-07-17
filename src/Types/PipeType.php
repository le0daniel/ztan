<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Value;

/**
 * @template TFirst
 * @template TSecond
 *
 * @extends BaseType<TSecond>
 */
final readonly class PipeType extends BaseType
{
    /**
     * @param Type<TFirst> $firstType
     * @param Type<TSecond> $secondType
     */
    public function __construct(
        public Type $firstType,
        public Type $secondType,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        $firstValue = $this->firstType->execute($value, $context);
        if ($firstValue === Value::INVALID) {
            return Value::INVALID;
        }
        return $this->secondType->execute($firstValue, $context);
    }
}