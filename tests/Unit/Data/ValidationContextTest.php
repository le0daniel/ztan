<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Data;

use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\IssueType;
use Le0daniel\Ztan\Data\ValidationContext;
use PHPUnit\Framework\TestCase;

final class ValidationContextTest extends TestCase
{
    public function testStartsWithNoIssues(): void
    {
        $context = new ValidationContext();

        self::assertSame([], $context->issues);
    }

    public function testAddIssueAtRootPath(): void
    {
        $context = new ValidationContext();

        $context->addIssue(Issue::custom('root error'));

        self::assertCount(1, $context->issues);
        self::assertSame('root error', $context->issues[0]->message);
        self::assertSame([], $context->issues[0]->path);
    }

    public function testAddIssueAtNestedPath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');
        $context->enterPath('name');

        $context->addIssue(Issue::custom('invalid name'));

        self::assertCount(1, $context->issues);
        self::assertSame('invalid name', $context->issues[0]->message);
        self::assertSame(['user', 'name'], $context->issues[0]->path);
    }

    public function testLeavePathRestoresParent(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');
        $context->enterPath('name');
        $context->leavePath();

        $context->addIssue(Issue::custom('user error'));

        self::assertCount(1, $context->issues);
        self::assertSame(['user'], $context->issues[0]->path);
    }

    public function testMultipleIssuesAtSamePath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('field');

        $context->addIssue(Issue::custom('first'));
        $context->addIssue(Issue::custom('second'));

        self::assertCount(2, $context->issues);
        self::assertSame('first', $context->issues[0]->message);
        self::assertSame('second', $context->issues[1]->message);
    }

    public function testIntegerPathSegment(): void
    {
        $context = new ValidationContext();
        $context->enterPath('items');
        $context->enterPath(0);

        $context->addIssue(Issue::custom('bad item'));

        self::assertCount(1, $context->issues);
        self::assertSame(['items', 0], $context->issues[0]->path);
    }

    public function testCloneForProbingPreservesPath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');
        $context->enterPath('name');

        $probe = $context->cloneForProbing();
        $probe->addIssue(Issue::custom('probe error'));

        self::assertCount(1, $probe->issues);
        self::assertSame(['user', 'name'], $probe->issues[0]->path);
    }

    public function testCloneForProbingStartsWithNoIssues(): void
    {
        $context = new ValidationContext();
        $context->addIssue(Issue::custom('original'));

        $probe = $context->cloneForProbing();

        self::assertSame([], $probe->issues);
    }

    public function testCloneForProbingDoesNotAffectOriginal(): void
    {
        $context = new ValidationContext();
        $context->enterPath('field');

        $probe = $context->cloneForProbing();
        $probe->addIssue(Issue::custom('probe only'));

        self::assertSame([], $context->issues);
    }

    public function testCloneForProbingPathIsIndependent(): void
    {
        $context = new ValidationContext();
        $context->enterPath('a');

        $probe = $context->cloneForProbing();
        $probe->enterPath('b');
        $probe->addIssue(Issue::custom('deep'));

        // Probe should have path a.b
        self::assertCount(1, $probe->issues);
        self::assertSame(['a', 'b'], $probe->issues[0]->path);

        // Original path should still be just 'a'
        $context->addIssue(Issue::custom('original'));
        self::assertCount(1, $context->issues);
        self::assertSame(['a'], $context->issues[0]->path);
    }

    public function testMergeIssuesCombinesBothContexts(): void
    {
        $context = new ValidationContext();
        $context->enterPath('a');
        $context->addIssue(Issue::custom('from original'));
        $context->leavePath();

        $other = new ValidationContext();
        $other->enterPath('b');
        $other->addIssue(Issue::custom('from other'));
        $other->leavePath();

        $context->mergeIssues($other);

        self::assertCount(2, $context->issues);
        self::assertSame('from original', $context->issues[0]->message);
        self::assertSame(['a'], $context->issues[0]->path);
        self::assertSame('from other', $context->issues[1]->message);
        self::assertSame(['b'], $context->issues[1]->path);
    }

    public function testMergeIssuesAppendsToExistingPath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('field');
        $context->addIssue(Issue::custom('first'));
        $context->leavePath();

        $other = new ValidationContext();
        $other->enterPath('field');
        $other->addIssue(Issue::custom('second'));
        $other->leavePath();

        $context->mergeIssues($other);

        self::assertCount(2, $context->issues);
        self::assertSame('first', $context->issues[0]->message);
        self::assertSame('second', $context->issues[1]->message);
    }

    public function testMergeIssuesDoesNotAffectSource(): void
    {
        $context = new ValidationContext();

        $other = new ValidationContext();
        $other->addIssue(Issue::custom('from other'));

        $context->mergeIssues($other);

        // Source should still have its issues untouched
        self::assertCount(1, $other->issues);

        // Target should also have them now
        self::assertCount(1, $context->issues);
    }

    public function testMergeEmptyContextIsNoop(): void
    {
        $context = new ValidationContext();
        $context->addIssue(Issue::custom('existing'));

        $empty = new ValidationContext();
        $context->mergeIssues($empty);

        self::assertCount(1, $context->issues);
        self::assertSame('existing', $context->issues[0]->message);
    }

    public function testCloneForProbingThenMergeBackRoundTrip(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');

        $probe = $context->cloneForProbing();
        $probe->addIssue(Issue::custom('probed issue'));

        // Issues stay in probe, not in original
        self::assertSame([], $context->issues);

        // After merge, original gets them
        $context->mergeIssues($probe);
        self::assertCount(1, $context->issues);
        self::assertSame('probed issue', $context->issues[0]->message);
        self::assertSame(['user'], $context->issues[0]->path);
    }
}
