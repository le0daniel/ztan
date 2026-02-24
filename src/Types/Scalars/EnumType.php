<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Scalars;

use BackedEnum;
use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Pipe;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;
use Le0daniel\Ztan\Types\Pipe\Enums\Not;
use Le0daniel\Ztan\Types\Pipe\Enums\Only;
use TypeError;
use UnitEnum;

/**
 * @template T of UnitEnum
 * @extends BaseType<T>
 */
final readonly class EnumType extends BaseType
{
    /**
     * @param class-string<T> $enumClass
     * @param list<Pipe<T>> $pipeline
     */
    public function __construct(
        private string $enumClass,
        private array $pipeline = [],
        private bool $coerce = false,
    )
    {
    }

    /**
     * @param Pipe<T> $pipe
     * @return self<T>
     */
    private function withPipe(Pipe $pipe): self
    {
        return new self($this->enumClass, [...$this->pipeline, $pipe], $this->coerce);
    }

    /**
     * @param list<T> $cases
     * @return self<T>
     */
    public function only(array $cases): self
    {
        return $this->withPipe(new Only($cases));
    }

    /**
     * @param list<T> $cases
     * @return self<T>
     */
    public function not(array $cases): self
    {
        return $this->withPipe(new Not($cases));
    }

    /**
     * @template E of UnitEnum
     * @param class-string<E> $enumClass
     */
    public static function coerceValue(string $enumClass, mixed $value): mixed
    {
        if (is_string($value)) {
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        }

        if (is_subclass_of($enumClass, BackedEnum::class) && (is_string($value) || is_int($value))) {
            try {
                $result = $enumClass::tryFrom($value);
                if ($result !== null) {
                    return $result;
                }
            } catch (TypeError) {
            }
        }

        return $value;
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if ($this->coerce) {
            $value = self::coerceValue($this->enumClass, $value);
        }

        if (!$value instanceof $this->enumClass) {
            $context->addIssue(Issue::invalidValue("Invalid value.", $value, metadata: ['expected_enum' => $this->enumClass]));
            return Value::INVALID;
        }

        foreach ($this->pipeline as $pipe) {
            $value = $pipe->execute($value, $context);
            if (Value::isInvalid($value)) {
                return $value;
            }
        }

        return $value;
    }
}
