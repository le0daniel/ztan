<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template T of object
 * @extends BaseType<T>
 */
final readonly class InstanceType extends BaseType
{
    /**
     * @param class-string<T> $className
     */
    public function __construct(
        private string $className,
    ) {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        if ($value instanceof $this->className) {
            return $value;
        }

        $context->addIssue(Issue::invalidType("Expected instance of {$this->className}.", $value, [
            'expected_class' => $this->className,
        ]));

        return Value::INVALID;
    }
}
