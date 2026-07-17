<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types;

use Closure;
use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @template TValue
 * @extends BaseType<TValue>
 * @phpstan-type ProcessingFn Closure(mixed): mixed
 */
final readonly class PreprocessType extends BaseType
{
    /**
     * @param Type<TValue> $assertion
     * @param ProcessingFn $processor
     */
    public function __construct(
        public Type     $assertion,
        private Closure $processor,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        try {
            $preprocessedValue = ($this->processor)($value);
        } catch (\Throwable $throwable) {
            $context->addIssue(Issue::fromException($throwable));
            return Value::INVALID;
        }

        return $this->assertion->execute(
            $preprocessedValue,
            $context
        );
    }
}