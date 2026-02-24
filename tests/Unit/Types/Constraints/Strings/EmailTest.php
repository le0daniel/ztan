<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Constraints\Strings;

use Le0daniel\Assertions\Data\IssueType;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\Strings\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validProvider(): iterable
    {
        yield 'basic' => ['user@example.com'];
        yield 'dots in local' => ['user.name@example.com'];
        yield 'plus tag' => ['user+tag@example.com'];
        yield 'subdomain' => ['user@sub.example.com'];
        yield 'multi-level TLD' => ['user@example.co.uk'];
        yield 'minimal' => ['a@b.cc'];
        yield 'numbers' => ['user123@example.com'];
        yield 'underscore' => ['user_name@example.com'];
        yield 'hyphen' => ['user-name@example.com'];
        yield 'apostrophe' => ["user'name@example.com"];
        yield 'uppercase' => ['USER@EXAMPLE.COM'];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsValidEmails(string $input): void
    {
        $pipe = new Email();
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
        yield 'no at sign' => ['plaintext'];
        yield 'missing local' => ['@example.com'];
        yield 'missing domain' => ['user@'];
        yield 'no TLD' => ['user@example'];
        yield 'short TLD' => ['user@example.c'];
        yield 'starts with dot' => ['.user@example.com'];
        yield 'ends with dot' => ['user.@example.com'];
        yield 'consecutive dots' => ['user..name@example.com'];
        yield 'space' => ['user @example.com'];
        yield 'empty' => [''];
        yield 'quoted local' => ['"quoted"@example.com'];
        yield 'IP domain' => ['user@[127.0.0.1]'];
        yield 'domain starts with hyphen' => ['user@-example.com'];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidEmails(string $input): void
    {
        $pipe = new Email();
        $context = new ValidationContext();

        $result = $pipe->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('String is not a valid email address.', $context->issues[0]->message);
        self::assertSame(IssueType::InvalidValue, $context->issues[0]->type);
        self::assertSame($input, $context->issues[0]->received);
    }
}
