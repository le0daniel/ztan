<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Closure;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\NullableType;

/**
 * @template TValue of string = string
 * @implements Type<TValue>
 *
 * @phpstan-type StringProcessorFn (Closure(string): string)
 * @phpstan-type StringConstraintFn (Closure(string): bool)
 */
final readonly class StringType implements Type
{
    /**
     * @param list<StringProcessorFn> $processors
     * @param list<StringConstraintFn> $constraints
     */
    public function __construct(
        private array $processors = [],
        private array $constraints = [],
    )
    {
    }

    /**
     * @return StringType<TValue>
     */
    public function trim(): StringType
    {
        return clone($this, [
            'processors' => [
                ... $this->processors,
                static fn(string $value) => trim($value),
            ]
        ]);
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        if (!is_string($value)) {
            $context->addIssue(new Issue("Expected string."));
            return Value::INVALID;
        }

        $value = array_reduce(
            $this->processors,
            static fn (string $value, Closure $processor): string => $processor($value),
            $value
        );

        if (array_any($this->constraints, static fn($constraint) => !$constraint($value))) {
            return Value::INVALID;
        }

        /** @var TValue $value */
        return $value;
    }

    /**
     * @return StringType<TValue&non-empty-string>
     */
    public function notEmpty(): StringType
    {
        return clone($this, [
            ... $this->constraints,
            static function (string $value): bool {
                return mb_strlen(trim($value)) > 0;
            }
        ]);
    }

    /**
     * @return NullableType<TValue>
     */
    public function nullable(): NullableType
    {
        return new NullableType($this);
    }

    /**
     * @param positive-int $length
     * @return StringType<TValue>
     */
    public function minLength(int $length): StringType
    {
        return clone($this, [
            ... $this->constraints,
            static function (string $value) use ($length): bool {
                return mb_strlen(trim($value)) >= $length;
            }
        ]);
    }
}