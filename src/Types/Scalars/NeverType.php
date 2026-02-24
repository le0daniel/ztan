<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Type<never>
 */
final readonly class NeverType implements Type
{
    public function execute(mixed $value, Context $context): Value
    {
        $context->addIssue(
            Issue::invalidValue('Should never be reached.', $value)
        );
        return Value::INVALID;
    }
}