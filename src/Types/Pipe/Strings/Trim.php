<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;

/**
 * @implements Pipe<string>
 */
final readonly class Trim implements Pipe
{
    public function execute(mixed $value, Context $context): string
    {
        return trim($value);
    }
}
