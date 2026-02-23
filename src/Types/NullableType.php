<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types;

use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Contracts\Context;

/**
 * @template TAssertionValue
 * @implements Type<null|TAssertionValue>
 */
final readonly class NullableType implements Type
{
    /**
     * @param Type<TAssertionValue> $assertion
     */
    public function __construct(
        private Type $assertion,
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