<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\PhpStan\InferredTemplates;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type as PhpStanType;

/**
 * Keeps literal types through the BaseType methods declaring their own template
 * (->pipe(Type<T>), ->transform(Closure(TValue): T)). See InferredTemplates.
 */
final readonly class InferredTemplateMethodResolver implements DynamicMethodReturnTypeExtension
{
    private const array METHODS = ['pipe', 'transform'];

    public function getClass(): string
    {
        return BaseType::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), self::METHODS, true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?PhpStanType {
        return InferredTemplates::returnType($methodReflection, $methodCall->getArgs(), $scope);
    }
}
