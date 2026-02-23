<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types;

use Closure;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Contracts\Context;

/**
 * @template TValue
 * @implements Type<TValue>
 * @phpstan-type ProcessingFn Closure(mixed): mixed
 */
final readonly class PreProcessType implements Type
{
    /**
     * @param Type<TValue> $assertion
     * @param ProcessingFn $processor
     */
    public function __construct(
        private Type    $assertion,
        private Closure $processor,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        return $this->assertion->execute(
            ($this->processor)($value),
            $context
        );
    }
}