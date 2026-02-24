<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Lists;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<list<mixed>>
 */
final readonly class MinItems implements Pipe
{
    public function __construct(
        private int $count,
        private bool $including = true,
    ) {
    }

    /**
     * @param list<mixed> $value
     * @return list<mixed>|Value::INVALID
     */
    public function execute(mixed $value, Context $context): array|Value
    {
        $actualCount = count($value);
        $isValid = $this->including
            ? $actualCount >= $this->count
            : $actualCount > $this->count;

        if ($isValid) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Too few items.', $value, [
            'min_items' => $this->count,
            'including' => $this->including,
            'actual_count' => $actualCount,
        ]));

        return Value::INVALID;
    }
}
