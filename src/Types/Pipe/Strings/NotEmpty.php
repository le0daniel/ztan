<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<string>
 */
final readonly class NotEmpty implements Pipe
{
    public function execute(mixed $value, Context $context): string|Value
    {
        if ($value !== '') {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('String must not be empty.', $value));

        return Value::INVALID;
    }
}
