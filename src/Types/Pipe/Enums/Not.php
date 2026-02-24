<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Pipe\Enums;

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Exceptions\InvalidSchemaException;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use UnitEnum;

/**
 * @template T of UnitEnum
 * @implements Pipe<T>
 */
final readonly class Not implements Pipe
{
    /** @param list<T> $cases */
    public function __construct(private array $cases)
    {
        if ($cases === []) {
            throw new InvalidSchemaException('At least one case must be provided.');
        }
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if (!in_array($value, $this->cases, true)) {
            return $value;
        }

        $context->addIssue(Issue::invalidValue('Value is not allowed.', $value, [
            'disallowed' => array_map(fn(UnitEnum $case) => $case->name, $this->cases),
        ]));

        return Value::INVALID;
    }
}
