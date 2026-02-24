<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<string>
 */
final readonly class Regex implements Pipe
{
    public function __construct(
        private string $pattern,
    ) {
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        if (preg_match($this->pattern, $value) === 1) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('String does not match the expected pattern.', $value, [
            'pattern' => $this->pattern,
        ]));

        return Value::INVALID;
    }
}
