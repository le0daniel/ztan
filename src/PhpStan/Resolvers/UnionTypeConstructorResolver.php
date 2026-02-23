<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan\Resolvers;

use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Types\Complex\UnionType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type as PhpStanType;
use PHPStan\Type\TypeCombinator;

final readonly class UnionTypeConstructorResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return UnionType::class;
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
        if ($args === []) {
            return null;
        }

        $valueTypes = [];
        foreach ($args as $arg) {
            $argType = $scope->getType($arg->value);
            $valueTypes[] = $argType->getTemplateType(Type::class, 'TValue');
        }

        $combined = TypeCombinator::union(...$valueTypes);

        return new GenericObjectType(UnionType::class, [$combined]);
    }
}
