<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use DateTimeImmutable;
use DateTimeInterface;
use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Pipe\DateTimes\After;
use Le0daniel\Assertions\Types\Pipe\DateTimes\Before;
use Psr\Clock\ClockInterface;

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

    /**
     * @param Pipe<DateTimeImmutable> $pipe
     */
    private function withPipe(Pipe $pipe): self
    {
        return new self($this->format, [...$this->pipeline, $pipe]);
    }

    public function after(DateTimeInterface $threshold): self
    {
        return $this->withPipe(new After($threshold));
    }

    public function before(DateTimeInterface $threshold): self
    {
        return $this->withPipe(new Before($threshold));
    }

    public function between(DateTimeInterface $start, DateTimeInterface $end): self
    {
        return $this->withPipe(new After($start, including: true))
            ->withPipe(new Before($end, including: true));
    }

    public function past(?ClockInterface $clock = null): self
    {
        return $this->withPipe(new Before(static fn() => $clock?->now() ?? new DateTimeImmutable()));
    }

    public function future(?ClockInterface $clock = null): self
    {
        return $this->withPipe(new After(static fn() => $clock?->now() ?? new DateTimeImmutable()));
    }

    public function execute(mixed $value, Context $context): DateTimeImmutable|Value
    {
        if (!is_string($value)) {
            $context->addIssue(Issue::invalidType("Expected string.", $value));
            return Value::INVALID;
        }

        $dateTime = DateTimeImmutable::createFromFormat($this->format, $value);

        if ($dateTime === false || $dateTime->format($this->format) !== $value) {
            $context->addIssue(Issue::invalidValue("Invalid date format.", $value, metadata: ['format' => $this->format]));
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
