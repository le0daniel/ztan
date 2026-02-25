<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Tests\Unit\Data;

use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\IssueType;
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
        self::assertNull($issue->debugMessage);
        self::assertSame('Expected string.', $issue->getMessage());
        self::assertSame('[InvalidType] Expected string. Received: int<42>.', $issue->getMessage(true));
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
        self::assertNull($issue->debugMessage);
        self::assertSame('Invalid value.', $issue->getMessage());
        self::assertSame("[InvalidValue] Invalid value. Received: string<'bad'>.", $issue->getMessage(true));
    }

    public function testMissingValue(): void
    {
        $issue = Issue::missingValue('Property name is required.', ['property' => 'name']);

        self::assertSame('Property name is required.', $issue->message);
        self::assertSame(IssueType::MissingValue, $issue->type);
        self::assertNull($issue->received);
        self::assertSame(['property' => 'name'], $issue->metadata);
        self::assertNull($issue->debugMessage);
        self::assertSame('Property name is required.', $issue->getMessage());
        self::assertSame('[MissingValue] Property name is required. Received: NULL.', $issue->getMessage(true));
    }

    public function testCustom(): void
    {
        $issue = Issue::custom('Must be positive.', -5);

        self::assertSame('Must be positive.', $issue->message);
        self::assertSame(IssueType::Custom, $issue->type);
        self::assertSame(-5, $issue->received);
        self::assertNull($issue->debugMessage);
        self::assertSame('Must be positive.', $issue->getMessage());
        self::assertSame('[Custom] Must be positive. Received: int<-5>.', $issue->getMessage(true));
    }

    public function testCustomWithoutReceived(): void
    {
        $issue = Issue::custom('Something went wrong.');

        self::assertNull($issue->received);
        self::assertNull($issue->debugMessage);
        self::assertSame('Something went wrong.', $issue->getMessage());
        self::assertSame('[Custom] Something went wrong. Received: NULL.', $issue->getMessage(true));
    }

    public function testGetMessageWithDebugMessage(): void
    {
        $issue = new Issue(
            message: 'Expected string.',
            type: IssueType::InvalidType,
            received: 42,
            debugMessage: 'Value came from user input.',
        );

        self::assertSame('Expected string.', $issue->getMessage());
        self::assertSame('[InvalidType] Expected string. Received: int<42>. Value came from user input.', $issue->getMessage(true));
    }

    public function testWithPath(): void
    {
        $issue = Issue::invalidType('Expected string.', 42);
        $withPath = $issue->prependPath(['user', 'name']);

        self::assertSame(['user', 'name'], $withPath->path);
        self::assertSame([], $issue->path);
        self::assertSame($issue->message, $withPath->message);
        self::assertSame($issue->type, $withPath->type);
        self::assertSame($issue->received, $withPath->received);
        self::assertSame($issue->metadata, $withPath->metadata);
        self::assertNull($issue->debugMessage);
        self::assertNull($withPath->debugMessage);
    }

    public function testGetPathAsString(): void
    {
        $issue = Issue::invalidType('Expected string.', 42);

        self::assertSame('', $issue->getPathAsString());

        $withPath = $issue->prependPath(['user', 'name']);
        self::assertSame('user.name', $withPath->getPathAsString());
    }

    public function testGetPathAsStringWithIntSegment(): void
    {
        $issue = Issue::invalidType('Expected string.', 42);
        $withPath = $issue->prependPath(['items', 0, 'name']);

        self::assertSame('items.0.name', $withPath->getPathAsString());
    }
}
