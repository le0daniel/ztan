<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Data;

use Le0daniel\Assertions\Contracts\Context;

final class ValidationContext implements Context
{
    /**
     * @param list<Issue> $issues
     * @param list<int|string> $path
     */
    public function __construct(
        private(set) array $issues = [],
        private(set) array $path = [],
    )
    {
    }

    public function addIssue(Issue $issue): void
    {
        $this->issues[] = $issue->withPath($this->path);
    }

    public function enterPath(int|string $path): void
    {
        $this->path[] = $path;
    }

    public function leavePath(): void
    {
        array_pop($this->path);
    }

    public function cloneForProbing(): Context
    {
        return new self(path: $this->path);
    }

    public function mergeIssues(Context $other): void
    {
        $this->issues = [...$this->issues, ...$other->issues];
    }
}
