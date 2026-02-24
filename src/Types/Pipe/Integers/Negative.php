<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Integers;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<int>
 */
final readonly class Negative implements Pipe
{
    private Lt $lt;

    public function __construct()
    {
        $this->lt = new Lt(0);
    }

    public function execute(mixed $value, Context $context): int|Value
    {
        return $this->lt->execute($value, $context);
    }
}
