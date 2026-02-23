<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

final class ValidationException extends \RuntimeException
{
    /**
     * @param array<string, list<Issue>> $issues
     */
    public function __construct(
        public readonly array $issues,
    ) {
        parent::__construct($this->formatMessage());
    }

    private function formatMessage(): string
    {
        $lines = [];
        foreach ($this->issues as $path => $issues) {
            $prefix = $path === '' ? '' : "{$path}: ";
            foreach ($issues as $issue) {
                $lines[] = "{$prefix}{$issue->message}";
            }
        }

        return 'Validation failed: ' . implode('; ', $lines);
    }
}
