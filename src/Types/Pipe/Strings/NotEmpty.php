<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Strings;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

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
