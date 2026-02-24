<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan\Resolvers;

use Le0daniel\Assertions\Data\Value;
use Le0daniel\Assertions\PhpStan\TypeResolver;
use Le0daniel\Assertions\Types\Complex\ObjectShapeType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ObjectShapeType as PhpStanObjectShapeType;
use PHPStan\Type\Type as PhpStanType;
use PHPStan\Type\TypeCombinator;

final readonly class ObjectStructTypeResolver implements TypeResolver
{
    public function getTargetClass(): string
    {
        return ObjectShapeType::class;
    }

    public function resolve(PhpStanType $callerType, MethodCall $methodCall, Scope $scope): ?PhpStanType
    {
        $propertiesType = $callerType->getTemplateType(ObjectShapeType::class, 'TProperties');

        if (!$propertiesType instanceof PhpStanObjectShapeType) { // @phpstan-ignore phpstanApi.instanceofType
            return null;
        }

        return TypeCombinator::union(
            $propertiesType,
            new EnumCaseObjectType(Value::class, 'INVALID'),
        );
    }
}
