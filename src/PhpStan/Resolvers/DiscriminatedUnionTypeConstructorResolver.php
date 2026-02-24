<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Contracts\Shape;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Types\Complex\DiscriminatedUnionType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type as PhpStanType;
use PHPStan\Type\TypeCombinator;

final readonly class DiscriminatedUnionTypeConstructorResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return DiscriminatedUnionType::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === '__construct';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?PhpStanType {
        $args = $methodCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $arrayType = $scope->getType($args[1]->value);
        $constantArrays = $arrayType->getConstantArrays();
        if ($constantArrays === []) {
            return null;
        }

        $shapeType = new ObjectType(Shape::class);
        $valueTypes = [];

        foreach ($constantArrays as $constantArray) {
            foreach ($constantArray->getValueTypes() as $itemType) {
                if (!$shapeType->isSuperTypeOf($itemType)->yes()) {
                    return null;
                }

                $valueTypes[] = $itemType->getTemplateType(Type::class, 'TValue');
            }
        }

        if ($valueTypes === []) {
            return null;
        }

        $combined = TypeCombinator::union(...$valueTypes);

        return new GenericObjectType(DiscriminatedUnionType::class, [$combined]);
    }
}
