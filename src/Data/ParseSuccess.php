<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

/**
 * @template TValue
 */
final readonly class ParseSuccess
{
    /**
     * @param TValue $data
     */
    public function __construct(
        public mixed $data,
    ) {
    }
}
