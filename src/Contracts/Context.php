<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Contracts;

use Le0daniel\Assertions\Data\Issue;

interface Context
{
    /** @var list<Issue> */
    public array $issues { get; }

    public function addIssue(Issue $issue): void;
    public function enterPath(string|int $path): void;
    public function leavePath(): void;

    public function cloneForProbing(): Context;
    public function mergeIssues(Context $other): void;
}
