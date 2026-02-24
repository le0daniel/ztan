<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Integers;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<int>
 */
final readonly class Range implements Pipe
{
    public function __construct(
        private int $min,
        private int $max,
        private bool $including = true,
    ) {
    }

    public function execute(mixed $value, Context $context): int|Value
    {
        $isValid = $this->including
            ? $value >= $this->min && $value <= $this->max
            : $value > $this->min && $value < $this->max;

        if ($isValid) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Value is out of range.', $value, [
            'min' => $this->min,
            'max' => $this->max,
            'including' => $this->including,
            'actual' => $value,
        ]));

        return Value::INVALID;
    }
}
