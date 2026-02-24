<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Integers;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<int>
 */
final readonly class MultipleOf implements Pipe
{
    public function __construct(
        private int $divisor,
    ) {
    }

    public function execute(mixed $value, Context $context): int|Value
    {
        if ($value % $this->divisor === 0) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Value is not a multiple of the expected number.', $value, [
            'divisor' => $this->divisor,
            'actual' => $value,
        ]));

        return Value::INVALID;
    }
}
