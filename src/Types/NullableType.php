<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;

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