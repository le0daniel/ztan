<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Strings;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<string>
 */
final readonly class MinLength implements Pipe
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
            ? $actualLength >= $this->length
            : $actualLength > $this->length;

        if ($isValid) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('String is too short.', $value, [
            'min_length' => $this->length,
            'including' => $this->including,
            'actual_length' => $actualLength,
        ]));

        return Value::INVALID;
    }
}
