<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

final class ValidationException extends \RuntimeException
{
    /**
     * @param list<Issue> $issues
     */
    public function __construct(
        public readonly array $issues,
    ) {
        parent::__construct($this->formatMessage());
    }

    private function formatMessage(): string
    {
        $lines = [];
        foreach ($this->issues as $issue) {
            $path = $issue->getPathAsString();
            $prefix = $path === '' ? '' : "{$path}: ";
            $lines[] = "{$prefix}{$issue->message}";
        }

        return 'Validation failed: ' . implode('; ', $lines);
    }
}
