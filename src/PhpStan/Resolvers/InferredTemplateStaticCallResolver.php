<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Contracts\BaseType;
use Le0daniel\Ztan\PhpStan\InferredTemplates;
use Le0daniel\Ztan\Types\CatchType;
use Le0daniel\Ztan\Types\Complex\ListType;
use Le0daniel\Ztan\Types\Complex\RecordType;
use Le0daniel\Ztan\Types\NullableType;
use Le0daniel\Ztan\Types\PipeType;
use Le0daniel\Ztan\Types\PreprocessType;
use Le0daniel\Ztan\Types\RefineType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type as PhpStanType;

/**
 * Keeps literal types of wrapped schemas for `new` on the types below (their class
 * templates are inferred from the wrapped Type<T>), and for the Ztan factories
 * routed here by ZtanStaticMethodReturnTypeExtension. See InferredTemplates.
 */
final readonly class InferredTemplateStaticCallResolver implements DynamicStaticMethodReturnTypeExtension
{
    private const array CONSTRUCTORS = [
        ListType::class,
        RecordType::class,
        NullableType::class,
        CatchType::class,
        RefineType::class,
        PreprocessType::class,
        PipeType::class,
    ];

    public function getClass(): string
    {
        return BaseType::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === '__construct'
            && in_array($methodReflection->getDeclaringClass()->getName(), self::CONSTRUCTORS, true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?PhpStanType {
        if ($methodReflection->getName() === '__construct') {
            return InferredTemplates::instantiation($methodReflection, $methodCall->getArgs(), $scope);
        }

        return InferredTemplates::returnType($methodReflection, $methodCall->getArgs(), $scope);
    }
}
