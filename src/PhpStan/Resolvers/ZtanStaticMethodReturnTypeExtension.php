<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Ztan;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

final readonly class ZtanStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    /** @var array<string, DynamicStaticMethodReturnTypeExtension> */
    private array $resolverMap;

    public function __construct(
        ArrayShapeTypeConstructorResolver $arrayShapeResolver,
        ObjectShapeTypeConstructorResolver $objectShapeResolver,
        TupleTypeConstructorResolver $tupleResolver,
        InferredTemplateStaticCallResolver $inferredTemplateResolver,
    ) {
        $this->resolverMap = [
            'arrayShape' => $arrayShapeResolver,
            'objectShape' => $objectShapeResolver,
            'union' => $inferredTemplateResolver,
            'tuple' => $tupleResolver,
            'discriminatedUnion' => $inferredTemplateResolver,
            'list' => $inferredTemplateResolver,
            'record' => $inferredTemplateResolver,
        ];
    }

    public function getClass(): string
    {
        return Ztan::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return isset($this->resolverMap[$methodReflection->getName()]);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?Type {
        $resolver = $this->resolverMap[$methodReflection->getName()] ?? null;
        if ($resolver === null) {
            return null;
        }

        return $resolver->getTypeFromStaticMethodCall($methodReflection, $methodCall, $scope);
    }
}
