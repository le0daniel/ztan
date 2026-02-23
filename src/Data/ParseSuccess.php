<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

/**
 * @template TValue
 */
final readonly class ParseSuccess
{
    /**
     * @param TValue $data
     * @param list<Issue> $issues
     */
    public function __construct(
        public mixed $data,
        public array $issues = [],
    ) {
    }

    public function isPartial(): bool
    {
        return count($this->issues) !== 0;
    }
}
