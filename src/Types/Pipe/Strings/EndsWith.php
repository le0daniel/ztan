<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<string>
 */
final readonly class EndsWith implements Pipe
{
    public function __construct(
        private string $suffix,
    ) {
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        if (str_ends_with($value, $this->suffix)) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('String does not end with the expected suffix.', $value, [
            'suffix' => $this->suffix,
        ]));

        return Value::INVALID;
    }
}
