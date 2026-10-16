<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\PhpStan\InferredTemplates;
use Le0daniel\Ztan\Types\TransformType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type as PhpStanType;

/**
 * `new TransformType($type, $fn)` infers TValue and TTransformedValue in one pass, so
 * PHPStan types the closure before TValue is known: an untyped parameter leaks the
 * unresolved template into the result (array{TValue}) and offsets on it become mixed.
 * Resolving it as `$type->transform($fn)` binds TValue first, exactly like the method.
 */
final readonly class TransformTypeConstructorResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return TransformType::class;
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
        if (!isset($args[0], $args[1])) {
            return null;
        }

        $valueType = $scope->getType($args[0]->value)->getTemplateType(Type::class, 'TValue');
        $transform = new GenericObjectType(BaseType::class, [$valueType])->getMethod('transform', $scope);

        return InferredTemplates::returnType($transform, [$args[1]], $scope);
    }
}
