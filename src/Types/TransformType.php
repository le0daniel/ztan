<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types;

use Closure;
use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @template TValue
 * @template TTransformedValue
 * @extends BaseType<TTransformedValue>
 *
 * @phpstan-type TransformFn (Closure(TValue): TTransformedValue)
 */
final readonly class TransformType extends BaseType
{
    /**
     * @param Type<TValue> $assertion
     * @param TransformFn $transformFn
     */
    public function __construct(
        public Type     $assertion,
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

        try {
            return ($this->transformFn)($result);
        } catch (\Throwable $e) {
            $context->addIssue(Issue::fromException($e));
            return Value::INVALID;
        }
    }
}