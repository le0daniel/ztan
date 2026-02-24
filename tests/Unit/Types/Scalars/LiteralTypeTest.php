<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Tests\Unit\Types\Scalars;

use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Scalars\LiteralType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum LiteralTestSuit {
    case HEARTS;
    case DIAMONDS;
}

enum LiteralTestBackedSuit: string {
    case HEARTS = 'hearts';
    case DIAMONDS = 'diamonds';
}

final class LiteralTypeTest extends TestCase
{
    // --- Without coercion: accepts exact value ---

    /**
     * @return iterable<string, array{mixed, mixed}>
     */
    public static function exactMatchProvider(): iterable
    {
        yield 'string hello' => [new LiteralType('hello'), 'hello'];
        yield 'int 42' => [new LiteralType(42), 42];
        yield 'float 3.14' => [new LiteralType(3.14), 3.14];
        yield 'bool true' => [new LiteralType(true), true];
        yield 'bool false' => [new LiteralType(false), false];
        yield 'enum HEARTS' => [new LiteralType(LiteralTestSuit::HEARTS), LiteralTestSuit::HEARTS];
    }

    #[DataProvider('exactMatchProvider')]
    public function testAcceptsExactMatch(LiteralType $type, mixed $input): void
    {
        $context = new ValidationContext();
        $result = $type->execute($input, $context);

        self::assertSame($input, $result);
        self::assertSame([], $context->issues);
    }

    // --- Without coercion: rejects non-matching values ---

    /**
     * @return iterable<string, array{mixed, mixed}>
     */
    public static function rejectsStringProvider(): iterable
    {
        yield 'different string' => ['hello', 'world'];
        yield 'empty string' => ['hello', ''];
        yield 'int' => ['hello', 42];
        yield 'float' => ['hello', 3.14];
        yield 'true' => ['hello', true];
        yield 'false' => ['hello', false];
        yield 'null' => ['hello', null];
        yield 'array' => ['hello', []];
        yield 'object' => ['hello', new \stdClass()];
    }

    #[DataProvider('rejectsStringProvider')]
    public function testStringLiteralRejectsNonMatch(string $literal, mixed $input): void
    {
        $type = new LiteralType($literal);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
        self::assertSame('Invalid value.', $context->issues[0]->message);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectsIntProvider(): iterable
    {
        yield 'different int' => [99];
        yield 'float same value' => [42.0];
        yield 'string' => ['42'];
        yield 'true' => [true];
        yield 'null' => [null];
        yield 'array' => [[]];
    }

    #[DataProvider('rejectsIntProvider')]
    public function testIntLiteralRejectsNonMatch(mixed $input): void
    {
        $type = new LiteralType(42);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectsFloatProvider(): iterable
    {
        yield 'different float' => [2.71];
        yield 'int' => [3];
        yield 'string' => ['3.14'];
        yield 'true' => [true];
        yield 'null' => [null];
    }

    #[DataProvider('rejectsFloatProvider')]
    public function testFloatLiteralRejectsNonMatch(mixed $input): void
    {
        $type = new LiteralType(3.14);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectsBoolTrueProvider(): iterable
    {
        yield 'false' => [false];
        yield 'int 1' => [1];
        yield 'string true' => ['true'];
        yield 'null' => [null];
    }

    #[DataProvider('rejectsBoolTrueProvider')]
    public function testBoolTrueLiteralRejectsNonMatch(mixed $input): void
    {
        $type = new LiteralType(true);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectsBoolFalseProvider(): iterable
    {
        yield 'true' => [true];
        yield 'int 0' => [0];
        yield 'string false' => ['false'];
        yield 'null' => [null];
    }

    #[DataProvider('rejectsBoolFalseProvider')]
    public function testBoolFalseLiteralRejectsNonMatch(mixed $input): void
    {
        $type = new LiteralType(false);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectsEnumProvider(): iterable
    {
        yield 'different case' => [LiteralTestSuit::DIAMONDS];
        yield 'string name' => ['HEARTS'];
        yield 'int' => [0];
        yield 'null' => [null];
    }

    #[DataProvider('rejectsEnumProvider')]
    public function testEnumLiteralRejectsNonMatch(mixed $input): void
    {
        $type = new LiteralType(LiteralTestSuit::HEARTS);
        $context = new ValidationContext();

        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    // --- With coercion ---

    /**
     * @return iterable<string, array{mixed, mixed, mixed}>
     */
    public static function coercionProvider(): iterable
    {
        // String '42' coerce: int and float coerce to string
        yield 'string 42 from int' => [new LiteralType('42', coerce: true), 42, '42'];
        yield 'string 42 from float' => [new LiteralType('42', coerce: true), 42.0, '42'];

        // String 'true' coerce: bool coerces to string
        yield 'string true from bool' => [new LiteralType('true', coerce: true), true, 'true'];

        // Int 42 coerce: float and string coerce to int
        yield 'int 42 from float' => [new LiteralType(42, coerce: true), 42.0, 42];
        yield 'int 42 from string' => [new LiteralType(42, coerce: true), '42', 42];

        // Float 3.14 coerce: string coerces to float
        yield 'float 3.14 from string' => [new LiteralType(3.14, coerce: true), '3.14', 3.14];

        // Bool true coerce
        yield 'bool true from int 1' => [new LiteralType(true, coerce: true), 1, true];
        yield 'bool true from float 1.0' => [new LiteralType(true, coerce: true), 1.0, true];
        yield 'bool true from string true' => [new LiteralType(true, coerce: true), 'true', true];

        // Bool false coerce
        yield 'bool false from int 0' => [new LiteralType(false, coerce: true), 0, false];
        yield 'bool false from float 0.0' => [new LiteralType(false, coerce: true), 0.0, false];
        yield 'bool false from string false' => [new LiteralType(false, coerce: true), 'false', false];

        // Enum coerce
        yield 'enum HEARTS from string' => [new LiteralType(LiteralTestSuit::HEARTS, coerce: true), 'HEARTS', LiteralTestSuit::HEARTS];

        // Backed enum coerce
        yield 'backed enum HEARTS from backed value' => [new LiteralType(LiteralTestBackedSuit::HEARTS, coerce: true), 'hearts', LiteralTestBackedSuit::HEARTS];
        yield 'backed enum HEARTS from case name' => [new LiteralType(LiteralTestBackedSuit::HEARTS, coerce: true), 'HEARTS', LiteralTestBackedSuit::HEARTS];
    }

    #[DataProvider('coercionProvider')]
    public function testCoercionAccepts(LiteralType $type, mixed $input, mixed $expected): void
    {
        $context = new ValidationContext();
        $result = $type->execute($input, $context);

        self::assertSame($expected, $result);
        self::assertSame([], $context->issues);
    }

    /**
     * @return iterable<string, array{mixed, mixed}>
     */
    public static function coercionStillRejectsProvider(): iterable
    {
        // Int 42 coerce: '99' coerces to 99 which != 42
        yield 'int 42 rejects string 99' => [new LiteralType(42, coerce: true), '99'];

        // Bool true coerce: 0 coerces to false which != true
        yield 'bool true rejects int 0' => [new LiteralType(true, coerce: true), 0];

        // Enum coerce: 'DIAMONDS' coerces to DIAMONDS which != HEARTS
        yield 'enum HEARTS rejects string DIAMONDS' => [new LiteralType(LiteralTestSuit::HEARTS, coerce: true), 'DIAMONDS'];

        // Enum coerce: int not coercible
        yield 'enum HEARTS rejects int' => [new LiteralType(LiteralTestSuit::HEARTS, coerce: true), 42];

        // Backed enum coerce: non-matching backed value
        yield 'backed enum HEARTS rejects diamonds' => [new LiteralType(LiteralTestBackedSuit::HEARTS, coerce: true), 'diamonds'];
    }

    #[DataProvider('coercionStillRejectsProvider')]
    public function testCoercionStillRejects(LiteralType $type, mixed $input): void
    {
        $context = new ValidationContext();
        $result = $type->execute($input, $context);

        self::assertSame(Value::INVALID, $result);
        self::assertCount(1, $context->issues);
    }

    // --- Edge cases ---

    public function testIntZeroRejectsFalse(): void
    {
        $type = new LiteralType(0);
        $context = new ValidationContext();

        self::assertSame(Value::INVALID, $type->execute(false, $context));
    }

    public function testIntZeroRejectsNull(): void
    {
        $type = new LiteralType(0);
        $context = new ValidationContext();

        self::assertSame(Value::INVALID, $type->execute(null, $context));
    }

    public function testEmptyStringRejectsFalse(): void
    {
        $type = new LiteralType('');
        $context = new ValidationContext();

        self::assertSame(Value::INVALID, $type->execute(false, $context));
    }

    public function testEmptyStringRejectsZero(): void
    {
        $type = new LiteralType('');
        $context = new ValidationContext();

        self::assertSame(Value::INVALID, $type->execute(0, $context));
    }

    public function testFloatZeroRejectsIntZero(): void
    {
        $type = new LiteralType(0.0);
        $context = new ValidationContext();

        self::assertSame(Value::INVALID, $type->execute(0, $context));
    }
}
