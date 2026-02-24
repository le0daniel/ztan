<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Contracts;

use Le0daniel\Ztan\Data\Value;

/**
 * @template-covariant TValue
 */
interface Type
{
    /**
     * @phpstan-assert-if-true TValue $value
     * @param mixed $value
     * @param Context $context
     * @return TValue|Value::INVALID
     */
    public function execute(mixed $value, Context $context): mixed;
}