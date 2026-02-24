<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe;

use Closure;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * Validates a given value type against a validator function.
 *
 * @template TValue
 * @implements Pipe<TValue>
 */
final readonly class Constraint implements Pipe
{
    /**
     * @param Closure(TValue): bool $validator
     * @param string $message
     */
    public function __construct(
        private Closure $validator,
        public string $message,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if (($this->validator)($value)) {
            return $value;
        }

        $context->addIssue(Issue::custom($this->message, $value));
        return Value::INVALID;
    }
}