<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Records;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<array<string, mixed>>
 */
final readonly class MaxRecords implements Pipe
{
    public function __construct(
        private int $count,
        private bool $including = true,
    ) {
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>|Value::INVALID
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

        $context->addIssue(Issue::invalidValue('Too many properties.', $value, [
            'max_properties' => $this->count,
            'including' => $this->including,
            'actual_count' => $actualCount,
        ]));

        return Value::INVALID;
    }
}
