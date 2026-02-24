<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

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
