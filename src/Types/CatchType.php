<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types;

use Closure;
use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TValue
 * @extends BaseType<TValue>
 *
 * @phpstan-type CatchFn (TValue|(Closure(): TValue))
 */
final readonly class CatchType extends BaseType
{
    /**
     * @param Type<TValue> $assertion
     * @param CatchFn $value
     */
    public function __construct(
        private Type  $assertion,
        private mixed $value,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        $result = $this->assertion->execute($value, $context);
        if (!Value::isInvalid($result)) {
            return $result;
        }

        return $this->value instanceof Closure
            ? ($this->value)()
            : $this->value;
    }
}