<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Integers;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<int>
 */
final readonly class LowerThan implements Pipe
{
    public function __construct(
        private int $threshold,
        private bool $including = false,
    ) {
    }

    public function execute(mixed $value, Context $context): int|Value
    {
        if ($this->including ? $value <= $this->threshold : $value < $this->threshold) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Value is too large.', $value, [
            'threshold' => $this->threshold,
            'including' => $this->including,
            'actual' => $value,
        ]));

        return Value::INVALID;
    }
}
