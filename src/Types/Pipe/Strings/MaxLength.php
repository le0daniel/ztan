<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Pipe\Strings;

use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @implements Pipe<string>
 */
final readonly class MaxLength implements Pipe
{
    public function __construct(
        private int $length,
        private bool $including = true,
    ) {
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        $actualLength = mb_strlen($value);
        $isValid = $this->including
            ? $actualLength <= $this->length
            : $actualLength < $this->length;

        if ($isValid) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('String is too long.', $value, [
            'max_length' => $this->length,
            'including' => $this->including,
            'actual_length' => $actualLength,
        ]));

        return Value::INVALID;
    }
}
