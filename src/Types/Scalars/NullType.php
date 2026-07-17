<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Scalars;

use Le0daniel\Ztan\Contracts\CarriesMetadata;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\HasMetadata;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Type<null>
 */
final readonly class NullType implements Type, HasMetadata
{
    use CarriesMetadata;

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