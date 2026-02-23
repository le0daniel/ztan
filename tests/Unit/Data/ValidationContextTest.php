<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Data;

use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\ValidationContext;
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

        $context->addIssue(new Issue('root error'));

        self::assertCount(1, $context->issues[''] ?? []);
        self::assertSame('root error', $context->issues[''][0]->message);
    }

    public function testAddIssueAtNestedPath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');
        $context->enterPath('name');

        $context->addIssue(new Issue('invalid name'));

        self::assertCount(1, $context->issues['user.name'] ?? []);
        self::assertSame('invalid name', $context->issues['user.name'][0]->message);
    }

    public function testLeavePathRestoresParent(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');
        $context->enterPath('name');
        $context->leavePath();

        $context->addIssue(new Issue('user error'));

        self::assertCount(1, $context->issues['user'] ?? []);
    }

    public function testMultipleIssuesAtSamePath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('field');

        $context->addIssue(new Issue('first'));
        $context->addIssue(new Issue('second'));

        self::assertCount(2, $context->issues['field']);
        self::assertSame('first', $context->issues['field'][0]->message);
        self::assertSame('second', $context->issues['field'][1]->message);
    }

    public function testIntegerPathSegment(): void
    {
        $context = new ValidationContext();
        $context->enterPath('items');
        $context->enterPath(0);

        $context->addIssue(new Issue('bad item'));

        self::assertCount(1, $context->issues['items.0'] ?? []);
    }

    public function testCloneForProbingPreservesPath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');
        $context->enterPath('name');

        $probe = $context->cloneForProbing();
        $probe->addIssue(new Issue('probe error'));

        self::assertCount(1, $probe->issues['user.name'] ?? []);
    }

    public function testCloneForProbingStartsWithNoIssues(): void
    {
        $context = new ValidationContext();
        $context->addIssue(new Issue('original'));

        $probe = $context->cloneForProbing();

        self::assertSame([], $probe->issues);
    }

    public function testCloneForProbingDoesNotAffectOriginal(): void
    {
        $context = new ValidationContext();
        $context->enterPath('field');

        $probe = $context->cloneForProbing();
        $probe->addIssue(new Issue('probe only'));

        self::assertSame([], $context->issues);
    }

    public function testCloneForProbingPathIsIndependent(): void
    {
        $context = new ValidationContext();
        $context->enterPath('a');

        $probe = $context->cloneForProbing();
        $probe->enterPath('b');
        $probe->addIssue(new Issue('deep'));

        // Probe should have path a.b
        self::assertCount(1, $probe->issues['a.b'] ?? []);

        // Original path should still be just 'a'
        $context->addIssue(new Issue('original'));
        self::assertCount(1, $context->issues['a'] ?? []);
        self::assertArrayNotHasKey('a.b', $context->issues);
    }

    public function testMergeIssuesCombinesBothContexts(): void
    {
        $context = new ValidationContext();
        $context->enterPath('a');
        $context->addIssue(new Issue('from original'));
        $context->leavePath();

        $other = new ValidationContext();
        $other->enterPath('b');
        $other->addIssue(new Issue('from other'));
        $other->leavePath();

        $context->mergeIssues($other);

        self::assertCount(1, $context->issues['a']);
        self::assertCount(1, $context->issues['b']);
        self::assertSame('from original', $context->issues['a'][0]->message);
        self::assertSame('from other', $context->issues['b'][0]->message);
    }

    public function testMergeIssuesAppendsToExistingPath(): void
    {
        $context = new ValidationContext();
        $context->enterPath('field');
        $context->addIssue(new Issue('first'));
        $context->leavePath();

        $other = new ValidationContext();
        $other->enterPath('field');
        $other->addIssue(new Issue('second'));
        $other->leavePath();

        $context->mergeIssues($other);

        self::assertCount(2, $context->issues['field']);
        self::assertSame('first', $context->issues['field'][0]->message);
        self::assertSame('second', $context->issues['field'][1]->message);
    }

    public function testMergeIssuesDoesNotAffectSource(): void
    {
        $context = new ValidationContext();

        $other = new ValidationContext();
        $other->addIssue(new Issue('from other'));

        $context->mergeIssues($other);

        // Source should still have its issues untouched
        self::assertCount(1, $other->issues['']);

        // Target should also have them now
        self::assertCount(1, $context->issues['']);
    }

    public function testMergeEmptyContextIsNoop(): void
    {
        $context = new ValidationContext();
        $context->addIssue(new Issue('existing'));

        $empty = new ValidationContext();
        $context->mergeIssues($empty);

        self::assertCount(1, $context->issues);
        self::assertSame('existing', $context->issues[''][0]->message);
    }

    public function testCloneForProbingThenMergeBackRoundTrip(): void
    {
        $context = new ValidationContext();
        $context->enterPath('user');

        $probe = $context->cloneForProbing();
        $probe->addIssue(new Issue('probed issue'));

        // Issues stay in probe, not in original
        self::assertSame([], $context->issues);

        // After merge, original gets them
        $context->mergeIssues($probe);
        self::assertCount(1, $context->issues['user'] ?? []);
        self::assertSame('probed issue', $context->issues['user'][0]->message);
    }
}
