<?php declare(strict_types=1);

namespace Le0daniel\Ztan\Types\Complex;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Context;
use Le0daniel\Ztan\Contracts\Shape;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Data\Issue;
use Le0daniel\Ztan\Data\Value;

/**
 * @template TValue
 * @extends BaseType<TValue>
 */
final readonly class DiscriminatedUnionType extends BaseType
{
    /**
     * @param string $discriminatorProperty
     * @param list<Shape&Type<mixed>> $shapes
     */
    public function __construct(
        private string $discriminatorProperty,
        private array $shapes,
    ) {
    }

    public function execute(mixed $value, Context $context): mixed
    {
        $probingContext = $context->cloneForProbing();
        foreach ($this->shapes as $shape) {
            $result = $shape->executeProperty($this->discriminatorProperty, $value, $probingContext);

            if (!Value::isInvalid($result)) {
                return $shape->execute($value, $context);
            }
        }

        $context->addIssue(Issue::invalidType(
            "Value does not match any type in the discriminated union.",
            $value,
        ));

        return Value::INVALID;
    }
}
