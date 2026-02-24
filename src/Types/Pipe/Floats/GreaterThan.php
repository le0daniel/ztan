<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Floats;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<float>
 */
final readonly class GreaterThan implements Pipe
{
    public function __construct(
        private float $threshold,
        private bool $including = false,
    ) {
    }

    public function execute(mixed $value, Context $context): float|Value
    {
        if ($this->including ? $value >= $this->threshold : $value > $this->threshold) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Value is too small.', $value, [
            'threshold' => $this->threshold,
            'including' => $this->including,
            'actual' => $value,
        ]));

        return Value::INVALID;
    }
}
