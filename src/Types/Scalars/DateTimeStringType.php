<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use DateTimeImmutable;
use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @extends BaseType<DateTimeImmutable>
 */
final readonly class DateTimeStringType extends BaseType
{
    /**
     * @param list<Pipe<DateTimeImmutable>> $pipeline
     */
    public function __construct(
        private string $format,
        private array $pipeline = [],
    )
    {
    }

    public function execute(mixed $value, Context $context): DateTimeImmutable|Value
    {
        if (!is_string($value)) {
            $context->addIssue(new Issue("Expected string."));
            return Value::INVALID;
        }

        $dateTime = DateTimeImmutable::createFromFormat($this->format, $value);

        if ($dateTime === false || $dateTime->format($this->format) !== $value) {
            $context->addIssue(new Issue("Expected datetime string matching format: {$this->format}."));
            return Value::INVALID;
        }

        foreach ($this->pipeline as $pipe) {
            $value = $pipe->execute($dateTime, $context);
            if (Value::isInvalid($value)) {
                return $value;
            }
            $dateTime = $value;
        }

        return $dateTime;
    }
}
