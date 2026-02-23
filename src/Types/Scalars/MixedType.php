<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;

/**
 * @implements Type<mixed>
 */
final readonly class MixedType implements Type
{
    public function execute(mixed $value, Context $context): mixed
    {
        return $value;
    }
}