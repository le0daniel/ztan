<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types;

use Closure;
use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Type;

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