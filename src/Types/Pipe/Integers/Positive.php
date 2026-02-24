<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Integers;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<int>
 */
final readonly class Positive implements Pipe
{
    private Gt $gt;

    public function __construct()
    {
        $this->gt = new Gt(0);
    }

    public function execute(mixed $value, Context $context): int|Value
    {
        return $this->gt->execute($value, $context);
    }
}
