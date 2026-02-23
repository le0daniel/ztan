<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan\Resolvers;

use Le0daniel\Assertions\Types\Scalars\LiteralType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type as PhpStanType;

final readonly class LiteralTypeConstructorResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return LiteralType::class;
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
        if (!isset($args[0])) {
            return null;
        }

        $literalType = $scope->getType($args[0]->value);

        return new GenericObjectType(LiteralType::class, [$literalType]);
    }
}
