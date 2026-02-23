<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Contracts;

use Closure;
use Le0daniel\Assertions\Data\ParseError;
use Le0daniel\Assertions\Data\ParseSuccess;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\ValidationException;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\CatchType;
use Le0daniel\Assertions\Types\NullableType;
use Le0daniel\Assertions\Types\PreprocessType;
use Le0daniel\Assertions\Types\RefineType;
use Le0daniel\Assertions\Types\TransformType;

/**
 * @template TValue
 * @implements Type<TValue>
 */
abstract readonly class BaseType implements Type
{
    /**
     * @param mixed $value
     * @return TValue
     * @throws ValidationException
     */
    public function parse(mixed $value): mixed
    {
        $context = new ValidationContext();
        $result = $this->execute($value, $context);

        if (Value::isInvalid($result)) {
            throw new ValidationException($context->issues);
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return ParseSuccess<TValue>|ParseError
     */
    public function safeParse(mixed $value): ParseSuccess|ParseError
    {
        $context = new ValidationContext();
        $result = $this->execute($value, $context);

        if (Value::isInvalid($result)) {
            return new ParseError($context->issues);
        }

        return new ParseSuccess($result, $context->issues);
    }

    /**
     * @return NullableType<TValue>
     */
    public function nullable(): NullableType
    {
        return new NullableType($this);
    }

    /**
     * @param TValue|Closure(): TValue $value
     * @return CatchType<TValue>
     */
    public function catch(mixed $value): CatchType
    {
        return new CatchType($this, $value);
    }

    /**
     * @template T
     * @param Closure(TValue): T $transformFn
     * @return TransformType<TValue, T>
     */
    public function transform(Closure $transformFn): TransformType
    {
        return new TransformType($this, $transformFn);
    }

    /**
     * @param Closure(TValue): bool $refiner
     * @param string $message
     * @return RefineType<TValue>
     */
    public function refine(Closure $refiner, string $message): RefineType
    {
        return new RefineType($this, $refiner, $message);
    }

    /**
     * @param Closure(mixed): mixed $processor
     * @return PreprocessType<TValue>
     */
    public function preprocess(Closure $processor): PreprocessType
    {
        return new PreprocessType($this, $processor);
    }
}
