<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types;

use Closure;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TValue
 * @template TTransformedValue
 * @implements Type<TTransformedValue>
 *
 * @phpstan-type TransformFn (Closure(TValue): TTransformedValue)
 */
final readonly class TransformType implements Type
{
    /**
     * @param Type<TValue> $assertion
     * @param TransformFn $transformFn
     */
    public function __construct(
        private Type    $assertion,
        private Closure $transformFn,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        $result = $this->assertion->execute($value, $context);
        if (Value::isInvalid($result)) {
            return Value::INVALID;
        }

        return ($this->transformFn)($result);
    }
}