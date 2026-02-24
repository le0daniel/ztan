<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Strings\Url;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validProvider(): iterable
    {
        yield 'https' => ['https://example.com'];
        yield 'http' => ['http://example.com'];
        yield 'with path' => ['https://example.com/path'];
        yield 'with query' => ['https://example.com/path?q=1'];
        yield 'with fragment' => ['https://example.com#frag'];
        yield 'ftp' => ['ftp://files.example.com'];
        yield 'with auth' => ['https://user:pass@example.com'];
        yield 'with port' => ['https://example.com:8080/path'];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidUrls(string $input): void
    {
        $pipe = new Url();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'not a url' => ['not-a-url'];
        yield 'empty' => [''];
        yield 'missing scheme' => ['://missing'];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidUrls(string $input): void
    {
        $pipe = new Url();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String is not a valid URL.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
    }

    public function testAcceptsMatchingProtocol(): void
    {
        $pipe = new Url(protocol: 'https');
        $context = new ValidationContext();

        $result = $pipe->execute('https://example.com', $context);

        self::assertSame('https://example.com', $result);
        self::assertSame([], $context->issues);
    }

    public function testRejectsMismatchedProtocol(): void
    {
        $pipe = new Url(protocol: 'https');
        $context = new ValidationContext();

        $result = $pipe->execute('http://example.com', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('URL protocol does not match.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame('https', $context->issues[0]->metadata['expected_protocol']);
        self::assertSame('http', $context->issues[0]->metadata['actual_protocol']);
    }

    public function testAcceptsMatchingHostname(): void
    {
        $pipe = new Url(hostname: 'example.com');
        $context = new ValidationContext();

        $result = $pipe->execute('https://example.com/path', $context);

        self::assertSame('https://example.com/path', $result);
        self::assertSame([], $context->issues);
    }

    public function testRejectsMismatchedHostname(): void
    {
        $pipe = new Url(hostname: 'example.com');
        $context = new ValidationContext();

        $result = $pipe->execute('https://other.com/path', $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('URL hostname does not match.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame('example.com', $context->issues[0]->metadata['expected_hostname']);
        self::assertSame('other.com', $context->issues[0]->metadata['actual_hostname']);
    }

    public function testNormalizeReturnsAsciiString(): void
    {
        $pipe = new Url(normalize: true);
        $context = new ValidationContext();

        $result = $pipe->execute('https://example.com', $context);

        self::assertIsString($result);
        self::assertSame([], $context->issues);
    }

    public function testWithoutNormalizeReturnsOriginal(): void
    {
        $pipe = new Url(normalize: false);
        $context = new ValidationContext();

        $input = 'https://example.com';
        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }
}
