<?php declare(strict_types=1);

namespace Le0daniel\Assertions\Types\Scalars;

use Closure;
use Le0daniel\Assertions\Contracts\BaseType;
use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Contracts\Pipe;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\Constraints\Constraint;
use Le0daniel\Assertions\Types\Constraints\TransformPipe;

/**
 * @extends BaseType<string>
 */
final readonly class StringType extends BaseType
{
    /**
     * @param list<Pipe<string>> $pipeline
     */
    public function __construct(
        private array $pipeline = [],
        private bool $coerce = false
    )
    {
    }

    public function execute(mixed $value, Context $context): string|Value
    {
        $value = $this->coerce ? match(gettype($value)) {
            'boolean' => $value ? 'true' : 'false',
            'integer', 'double', 'string' => (string) $value,
            default => $value,
        } : $value;

        if (!is_string($value)) {
            $context->addIssue(new Issue("Expected string."));
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

    // public function trim(): StringType
    // {
    //     return clone($this, [
    //         'pipeline' => [
    //             ... $this->pipeline,
    //             new TransformPipe(fn (string $value) => trim($value)),
    //         ]
    //     ]);
    // }

    /**
     * @return StringType
     */
    // public function notEmpty(): StringType
    // {
    //     return clone($this, [
    //         'pipeline' => [
    //             ... $this->pipeline,
    //             new Constraint(
    //                 static function (string $value): bool {
    //                     return trim($value) !== '';
    //                 },
    //                 'String must not be empty.'
    //             )
    //         ]
    //     ]);
    // }

    /**
     * @param positive-int $length
     */
    // public function minLength(int $length, bool $including = true): StringType
    // {
    //     return clone($this, [
    //         'pipeline' => [
    //             ... $this->pipeline,
    //             new Constraint(
    //                 static function (string $value) use ($length, $including): bool {
    //                     return $including
    //                         ? mb_strlen($value) >= $length
    //                         : mb_strlen($value) > $length;
    //                 },
    //                 "String must be at least {$length} characters long."
    //             ),
    //         ]
    //     ]);
    // }
}