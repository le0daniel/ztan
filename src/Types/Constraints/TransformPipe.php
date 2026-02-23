<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Constraints;

use Closure;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;

/**
 * @template TValue
 * @implements Pipe<TValue>
 */
final readonly class TransformPipe implements Pipe
{
    /**
     * @param Closure(TValue): TValue $transformFn
     */
    public function __construct(
        private Closure $transformFn,
    )
    {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        // Only mutates the value.
        return ($this->transformFn)($value);
    }
}