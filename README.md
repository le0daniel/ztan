# Ztan

Type-safe validation for PHP, inspired by [Zod](https://zod.dev). Parse unknown data, get fully typed results — with first-class [PHPStan](https://phpstan.org) support.

## Why?

PHP arrays are not type-safe. Existing assertion libraries either require boilerplate or don't emit PHPStan types. Ztan gives you a single, chainable API to validate input data and get precise static types — no manual `@var` annotations, no guessing.

## Requirements

- PHP 8.5+
- PHPStan 2.1+ (for static analysis)

## Installation

```bash
composer require le0daniel/ztan
```

Add the PHPStan extension to your `phpstan.neon`:

```neon
includes:
    - vendor/le0daniel/ztan/extension.neon
```

## Quick Example

```php
use Le0daniel\Ztan\Ztan;

$userSchema = Ztan::arrayShape([
    'name'  => Ztan::string()->trim()->notEmpty(),
    'email' => Ztan::string()->email(),
    'age'   => Ztan::int()->gte(0)->lte(150),
    'role?' => Ztan::enum(Role::class),
]);

// Parse — throws ValidationException on failure
$user = $userSchema->parse($input);

// PHPStan knows: array{name: string, email: string, age: int, role?: Role}
$user['name']; // string ✓
```

## API

### Scalars

```php
Ztan::string()    // StringType
Ztan::int()       // IntType
Ztan::float()     // FloatType
Ztan::bool()      // BoolType
Ztan::null()      // NullType
Ztan::mixed()     // MixedType
Ztan::never()     // NeverType
Ztan::literal('active')         // LiteralType<'active'>
Ztan::enum(Status::class)       // EnumType<Status>
Ztan::instance(DateTime::class) // InstanceType<DateTime>
Ztan::dateTimeString('Y-m-d')   // DateTimeStringType
```

### Collections

```php
Ztan::list(Ztan::int())                // list<int>
Ztan::record(Ztan::string())           // array<string, string>
Ztan::tuple(Ztan::string(), Ztan::int()) // array{string, int}
Ztan::arrayShape([...])                // array{key: type, ...}
Ztan::objectShape([...])               // object{key: type, ...}
```

### Unions

```php
Ztan::union(Ztan::string(), Ztan::int())  // string|int

Ztan::discriminatedUnion('type', [
    Ztan::arrayShape(['type' => Ztan::literal('a'), 'value' => Ztan::string()]),
    Ztan::arrayShape(['type' => Ztan::literal('b'), 'count' => Ztan::int()]),
])
```

### String Constraints

Order matters. Constraints are applied in the order they are defined.

```php
Ztan::string()
    ->trim()
    ->lowercase()
    ->uppercase()
    ->minLength(1)
    ->maxLength(255)
    ->startsWith('prefix')
    ->endsWith('suffix')
    ->regex('/^[a-z]+$/')
    ->notEmpty()
    ->email()
    ->url()
    ->webUrl()
```

### Number Constraints

Order matters. Constraints are applied in the order they are defined.

```php
Ztan::int()
    ->gt(0)->gte(0)
    ->lt(100)->lte(100)
    ->range(0, 100)
    ->positive()->negative()
    ->nonnegative()->nonpositive()
    ->multipleOf(5)

// Same for Ztan::float() (except multipleOf)
```

### Collection Constraints

Order matters. Constraints are applied in the order they are defined.

```php
Ztan::list(Ztan::string())
    ->minItems(1)->maxItems(10)
    ->nonEmpty()
    ->length(5)

Ztan::record(Ztan::int())
    ->minProperties(1)->maxProperties(10)
    ->nonEmpty()
```

### DateTime Constraints

Order matters. Constraints are applied in the order they are defined.

```php
Ztan::dateTimeString('Y-m-d')
    ->after(new DateTime('2020-01-01'))
    ->before(new DateTime('2030-01-01'))
    ->between($start, $end)
    ->past()
    ->future()
```

### Enum Constraints

Order matters. Constraints are applied in the order they are defined.

```php
Ztan::enum(Role::class)
    ->only([Role::Admin, Role::User])
    ->not([Role::Guest])
```

### Modifiers

```php
// Nullable — accepts null or the inner type
Ztan::string()->nullable()  // string|null

// Default on failure
Ztan::string()->catch('fallback')

// Transform the validated value
Ztan::string()->transform(fn(string $v): int => strlen($v))

// Custom validation
Ztan::string()->refine(fn(string $v): bool => str_contains($v, '@'), 'Must contain @')

// Preprocess before validation
Ztan::int()->preprocess(fn(mixed $v): mixed => is_string($v) ? (int) $v : $v)
```

### Coercion

Automatically coerce values before validation:

```php
Ztan::coerce()->string()  // int, float, bool → string
Ztan::coerce()->int()     // float, bool, numeric string → int
Ztan::coerce()->float()   // int, bool, numeric string → float
Ztan::coerce()->bool()    // 1/0, 'true'/'false' → bool
Ztan::coerce()->enum(Status::class) // string/int → enum case
```

### Optional Properties

Suffix the key with `?`:

```php
Ztan::arrayShape([
    'name'    => Ztan::string(),
    'middle?' => Ztan::string(), // optional
])
```

## Parsing

```php
// Throws ValidationException
$value = $schema->parse($input);

// Returns ParseSuccess or ParseError
$result = $schema->safeParse($input);

if ($result instanceof \Le0daniel\Ztan\Data\ParseError) {
    foreach ($result->issues as $issue) {
        echo $issue->getPathAsString() . ': ' . $issue->message;
    }
} else {
    $result->data; // validated value
}
```

## PHPStan

Ztan ships with a PHPStan extension that infers precise return types from your schemas — no manual annotations needed. After including `extension.neon`, calls to `->parse()` and `->safeParse()` are fully typed.

```php
$schema = Ztan::arrayShape([
    'id'   => Ztan::int(),
    'tags' => Ztan::list(Ztan::string()),
]);

$data = $schema->parse($input);
// PHPStan infers: array{id: int, tags: list<string>}
```
