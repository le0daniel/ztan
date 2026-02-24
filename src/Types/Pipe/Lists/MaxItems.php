<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Lists;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<list<mixed>>
 */
final readonly class MaxItems implements Pipe
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
            ? $actualCount <= $this->count
            : $actualCount < $this->count;

        if ($isValid) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Too many items.', $value, [
            'max_items' => $this->count,
            'including' => $this->including,
            'actual_count' => $actualCount,
        ]));

        return Value::INVALID;
    }
}
