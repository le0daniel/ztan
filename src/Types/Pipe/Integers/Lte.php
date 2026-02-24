<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Integers;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<int>
 */
final readonly class Lte implements Pipe
{
    public function __construct(
        private int $threshold,
    ) {
    }

    public function execute(mixed $value, Context $context): int|Value
    {
        if ($value <= $this->threshold) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Value is too large.', $value, [
            'threshold' => $this->threshold,
            'actual' => $value,
        ]));

        return Value::INVALID;
    }
}
