<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Contracts;

use Le0daniel\Assertions\Data\Value;

/**
 * @template TValue
 */
interface Pipe
{
    /**
     * @param TValue $value
     * @param Context $context
     * @return TValue|Value::INVALID
     */
    public function execute(mixed $value, Context $context): mixed;
}