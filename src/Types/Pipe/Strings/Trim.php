<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Strings;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;

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
