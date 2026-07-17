<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;

/**
 * @template TAssertionValue
 * @extends BaseType<null|TAssertionValue>
 */
final readonly class NullableType extends BaseType
{
    /**
     * @param Type<TAssertionValue> $assertion
     */
    public function __construct(
        public Type $assertion,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->assertion->execute($value, $context);
    }
}