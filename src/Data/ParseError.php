<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

final readonly class ParseError
{
    /**
     * @param array<string, list<Issue>> $issues
     */
    public function __construct(
        public array $issues,
    ) {
    }

    public function toException(): ValidationException
    {
        return new ValidationException($this->issues);
    }
}
