<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Strings;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<string>
 */
final readonly class Email implements Pipe
{
    private const PATTERN = '/^(?!\.)(?!.*\.\.)([a-z0-9_\'+\-\.]*)[a-z0-9_\'+\-]@([a-z0-9][a-z0-9\-]*\.)+[a-z]{2,}$/i';

    public function execute(mixed $value, Context $context): string|Value
    {
        if (preg_match(self::PATTERN, $value) === 1) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('String is not a valid email address.', $value));

        return Value::INVALID;
    }
}
