<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan\Resolvers;

use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\PhpStan\TypeResolver;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Type as PhpStanType;
use PHPStan\Type\TypeCombinator;

final readonly class ArrayStructTypeResolver implements TypeResolver
{
    public function getTargetClass(): string
    {
        return ArrayShapeType::class;
    }

    public function resolve(PhpStanType $callerType, MethodCall $methodCall, Scope $scope): ?PhpStanType
    {
        $propertiesType = $callerType->getTemplateType(ArrayShapeType::class, 'TProperties');

        if ($propertiesType->getConstantArrays() === []) {
            return null;
        }

        return TypeCombinator::union(
            $propertiesType,
            new EnumCaseObjectType(Value::class, 'INVALID'),
        );
    }
}
