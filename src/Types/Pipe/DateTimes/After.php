<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\DateTimes;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @implements Pipe<DateTimeImmutable>
 */
final readonly class After implements Pipe
{
    /**
     * @param DateTimeInterface|Closure(): DateTimeInterface $threshold
     */
    public function __construct(
        private DateTimeInterface|Closure $threshold,
        private bool $including = false,
    ) {
    }

    public function execute(mixed $value, Context $context): DateTimeImmutable|Value
    {
        $threshold = $this->threshold instanceof Closure ? ($this->threshold)() : $this->threshold;

        if ($this->including ? $value >= $threshold : $value > $threshold) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Date is too early.', $value, [
            'threshold' => $threshold->format(DateTimeInterface::ATOM),
            'including' => $this->including,
            'actual' => $value->format(DateTimeInterface::ATOM),
        ]));

        return Value::INVALID;
    }
}
