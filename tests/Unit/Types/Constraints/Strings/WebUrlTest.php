<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Strings\WebUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebUrlTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validProvider(): iterable
    {
        yield 'https' => ['https://example.com'];
        yield 'http' => ['http://example.com'];
        yield 'subdomain' => ['https://sub.example.com'];
        yield 'multi-level TLD' => ['https://example.co.uk'];
        yield 'with path query fragment' => ['https://example.com/path?q=1#frag'];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidWebUrls(string $input): void
    {
        $pipe = new WebUrl();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function wrongProtocolProvider(): iterable
    {
        yield 'ftp' => ['ftp://example.com', 'ftp'];
        yield 'ssh' => ['ssh://example.com', 'ssh'];
    }

    #[DataProvider('wrongProtocolProvider')]
    public function testRejectsWrongProtocol(string $input, string $expectedProtocol): void
    {
        $pipe = new WebUrl();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('URL must use http or https protocol.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($expectedProtocol, $context->issues[0]->metadata['actual_protocol']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function badHostnameProvider(): iterable
    {
        yield 'localhost' => ['https://localhost'];
        yield 'IP address' => ['https://127.0.0.1'];
        yield 'IPv6' => ['https://[::1]'];
        yield 'domain starts with hyphen' => ['https://-example.com'];
    }

    #[DataProvider('badHostnameProvider')]
    public function testRejectsBadHostname(string $input): void
    {
        $pipe = new WebUrl();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('URL must have a valid domain name.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notAUrlProvider(): iterable
    {
        yield 'not a url' => ['not-a-url'];
        yield 'empty' => [''];
    }

    #[DataProvider('notAUrlProvider')]
    public function testRejectsNonUrls(string $input): void
    {
        $pipe = new WebUrl();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String is not a valid web URL.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
    }
}
