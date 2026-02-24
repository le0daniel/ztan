<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

final readonly class NullType implements Type
{

    public function execute(mixed $value, Context $context): mixed
    {
        if ($value === null) {
            return null;
        }

        $context->addIssue(
            Issue::invalidValue('Value is not null.', $value)
        );
        return Value::INVALID;
    }
}