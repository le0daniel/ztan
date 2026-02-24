# Assertion Library - Review & Plan

## Architecture Summary

Zod-like validation library for PHP 8.5 with full PHPStan type inference at level 10.

**Core**: `Type<TValue>` interface with `execute(mixed, Context): TValue|Value::INVALID`
**Scalars**: `StringType`
**Complex**: `ArrayShapeType`, `RecordType`
**Modifiers**: `NullableType`, `CatchType`, `TransformType`, `PreProcessType`, `RefineType`
**PHPStan**: Custom extensions for constructor type inference, `execute()` return types, and closure parameter types.

---

## Issues Found

### Bug: Constraint messages are lost
`StringType:59-62` — When a constraint fails, a generic `"String does not match constraints."` error is added to context. The `Constraint::$message` property is never surfaced. Each failing constraint should add its own issue.

---

## What's Missing

### 1. `parse()` / `safeParse()` API (Critical)
Your example API shows `->parse(someValue)` but there's no `parse` method. Users currently must manually construct a `ValidationContext` and call `execute()`. Need:
- A `parse(mixed): TValue` method that throws on failure
- A `safeParse(mixed): ParseResult<TValue>` that returns a result object
- A `ValidationException` that carries the structured issues
- PHPStan extension for `parse()` that returns `TValue` (no `Value::INVALID`)

### 2. `Schema` factory class
Your example shows `Schema::string()` but there's no factory. Currently requires `new StringType()`. A static factory makes the API much cleaner and is the natural place to add `arrayShape()`, `int()`, `record()`, etc.

### 3. Missing scalar types
- `IntType` — `int` validation (with `min()`, `max()`, `positive()`, `negative()`)
- `FloatType` — `float` validation
- `BoolType` — `bool` validation
- `MixedType` / `AnyType` — passthrough, useful in shapes where you don't care about a value's type

### 4. Missing string constraints
- `maxLength(int)`
- `pattern(string $regex)` — regex matching
- `email()`, `url()`, `uuid()`, `ip()` — common format validators

### 5. Missing collection types
- `ListType<T>` — validates `list<T>` (sequential integer keys)
- `TupleType` — validates `array{string, int}` (positional)

### 6. Missing combinators
- `UnionType` — `Schema::union(Schema::string(), Schema::int())` → `string|int`
- `LiteralType` — `Schema::literal('active')` → `'active'`
- `EnumType` — `Schema::enum(Status::class)` → `Status`

### 7. Missing modifier types
- `DefaultType` — provides a default when value is missing/null (distinct from `CatchType` which catches validation failures)
- `LazyType` — for recursive/self-referencing schemas

### 8. Richer `Issue` structure
Currently `Issue` is just a message string. For programmatic error handling and i18n, consider:
- Error code/type (e.g., `'too_small'`, `'invalid_type'`)
- Expected vs received metadata
- The path should be on the Issue itself, not just in the context's map

### 9. Tests
No test files exist yet. Need unit tests for each type and integration tests for composed schemas.

---

## Suggested Implementation Order

1. **Fix bugs** (constraint messages, missing addIssue in ArrayShapeType)
2. **Add `parse()`/`safeParse()`** + `ValidationException` + `ParseResult`
3. **Add `Schema` factory**
4. **Add `IntType`, `FloatType`, `BoolType`**
5. **Add `ListType`**
6. **Add `UnionType`, `LiteralType`, `EnumType`**
7. **Add missing string constraints**
8. **Add PHPStan extensions** for new types
9. **Add tests**
10. **Add `DefaultType`, `LazyType`, `PipeType`** (if needed)
