<?php declare(strict_types=1);

use Le0daniel\Assertions\Contracts\Context;
use Le0daniel\Assertions\Data\Issue;
use Le0daniel\Assertions\Data\ValidationContext;
use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\Types\CatchType;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use Le0daniel\Assertions\Types\Complex\RecordType;
use Le0daniel\Assertions\Types\Scalars\StringType;
use Le0daniel\Assertions\Types\TransformType;
use function PHPStan\dumpType;

require_once __DIR__ . '/../vendor/autoload.php';

$context = new ValidationContext();

$value = ["name" => "       z9", 'nickname' => "sst"];

$struct = new ArrayShapeType([
    'name' => new StringType()
        ->notEmpty()
        ->trim()
        ->minLength(8),
    'nickname' => new CatchType(
        new StringType(),
        null,
    ),
    'record?' => new RecordType(new StringType()->notEmpty()),
    'other?' => new TransformType(
        new ArrayShapeType([
            'id' => new StringType()->notEmpty(),
        ]),
        function ($value) {
            // dumpType($value);
            return ['other' => $value['id']];
        }
    )
]);

// dumpType($struct);
$result = $struct->execute($value, $context);
// dumpType($result);

if (Value::isInvalid($result)) {
    foreach ($context->issues as $path => $issues) {
        echo "At {$path}: " . PHP_EOL;
        foreach ($issues as $issue) {
            echo "- {$issue->message}" . PHP_EOL;
        }
    }
    exit(1);
}

// dumpType($result);

var_dump($result);
echo PHP_EOL;
exit(0);

// Expected: array{name: string, nickname?: string}|Le0daniel\Assertions\Data\Value::INVALID
