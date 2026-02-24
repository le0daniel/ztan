<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<string>
 */
final readonly class StartsWith implements Pipe
{
    public function __construct(
        private string $prefix,
    ) {
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        if (str_starts_with($value, $this->prefix)) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('String does not start with the expected prefix.', $value, [
            'prefix' => $this->prefix,
        ]));

        return Value::INVALID;
    }
}
