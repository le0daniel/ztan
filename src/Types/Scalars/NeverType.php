<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Scalars;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

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