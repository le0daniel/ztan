<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Complex;

use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;

/**
 * @template TValue
 * @extends BaseType<TValue>
 */
final readonly class UnionType extends BaseType
{
    /** @var list<Type<mixed>> */
    private array $types;

    /**
     * @param Type<mixed> ...$types
     */
    public function __construct(Type ...$types)
    {
        $this->types = array_values($types);
    }

    public function execute(mixed $value, Context $context): mixed
    {
        $failedProbes = [];

        foreach ($this->types as $type) {
            $probe = $context->cloneForProbing();
            $result = $type->execute($value, $probe);

            if (!Value::isInvalid($result)) {
                $context->mergeIssues($probe);
                return $result;
            }

            $failedProbes[] = $probe;
        }

        foreach ($failedProbes as $probe) {
            $context->mergeIssues($probe);
        }

        $context->addIssue(Issue::invalidType("Value does not match any type in the union.", $value));

        return Value::INVALID;
    }
}
