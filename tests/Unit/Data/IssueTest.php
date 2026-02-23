<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Data;

use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\IssueType;
use PHPUnit\Framework\TestCase;

final class IssueTest extends TestCase
{
    public function testInvalidType(): void
    {
        $issue = Issue::invalidType('Expected string.', 42);

        self::assertSame('Expected string.', $issue->message);
        self::assertSame(IssueType::InvalidType, $issue->type);
        self::assertSame(42, $issue->received);
        self::assertSame([], $issue->path);
        self::assertSame([], $issue->metadata);
        self::assertSame('Expected string. Received: int<42>', $issue->debugMessage);
    }

    public function testInvalidTypeWithMetadata(): void
    {
        $issue = Issue::invalidType('Expected string.', 42, ['key' => 'value']);

        self::assertSame(['key' => 'value'], $issue->metadata);
    }

    public function testInvalidValue(): void
    {
        $issue = Issue::invalidValue('Invalid value.', 'bad', ['expected' => 'good']);

        self::assertSame('Invalid value.', $issue->message);
        self::assertSame(IssueType::InvalidValue, $issue->type);
        self::assertSame('bad', $issue->received);
        self::assertSame(['expected' => 'good'], $issue->metadata);
        self::assertSame("Invalid value. Received: string<'bad'>", $issue->debugMessage);
    }

    public function testMissingValue(): void
    {
        $issue = Issue::missingValue('Property name is required.', ['property' => 'name']);

        self::assertSame('Property name is required.', $issue->message);
        self::assertSame(IssueType::MissingValue, $issue->type);
        self::assertNull($issue->received);
        self::assertSame(['property' => 'name'], $issue->metadata);
        self::assertSame('Property name is required.', $issue->debugMessage);
    }

    public function testCustom(): void
    {
        $issue = Issue::custom('Must be positive.', -5);

        self::assertSame('Must be positive.', $issue->message);
        self::assertSame(IssueType::Custom, $issue->type);
        self::assertSame(-5, $issue->received);
        self::assertSame('Must be positive. Received: int<-5>', $issue->debugMessage);
    }

    public function testCustomWithoutReceived(): void
    {
        $issue = Issue::custom('Something went wrong.');

        self::assertNull($issue->received);
        self::assertSame('Something went wrong. Received: NULL', $issue->debugMessage);
    }

    public function testWithPath(): void
    {
        $issue = Issue::invalidType('Expected string.', 42);
        $withPath = $issue->withPath(['user', 'name']);

        self::assertSame(['user', 'name'], $withPath->path);
        self::assertSame([], $issue->path);
        self::assertSame($issue->message, $withPath->message);
        self::assertSame($issue->type, $withPath->type);
        self::assertSame($issue->received, $withPath->received);
        self::assertSame($issue->metadata, $withPath->metadata);
        self::assertSame($issue->debugMessage, $withPath->debugMessage);
    }

    public function testGetPathAsString(): void
    {
        $issue = Issue::invalidType('Expected string.', 42);

        self::assertSame('', $issue->getPathAsString());

        $withPath = $issue->withPath(['user', 'name']);
        self::assertSame('user.name', $withPath->getPathAsString());
    }

    public function testGetPathAsStringWithIntSegment(): void
    {
        $issue = Issue::invalidType('Expected string.', 42);
        $withPath = $issue->withPath(['items', 0, 'name']);

        self::assertSame('items.0.name', $withPath->getPathAsString());
    }
}
