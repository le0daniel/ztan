<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan;

use Le0daniel\Ztan\Contracts\Type;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type as PhpStanType;

final readonly class ExecuteReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /** @var array<string, TypeResolver> */
    private array $resolverMap;

    /**
     * @param list<TypeResolver> $resolvers
     */
    public function __construct(array $resolvers)
    {
        $map = [];
        foreach ($resolvers as $resolver) {
            $map[$resolver->getTargetClass()] = $resolver;
        }
        $this->resolverMap = $map;
    }

    public function getClass(): string
    {
        return Type::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'execute';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?PhpStanType {
        $callerType = $scope->getType($methodCall->var);

        foreach ($callerType->getObjectClassNames() as $className) {
            if (isset($this->resolverMap[$className])) {
                return $this->resolverMap[$className]->resolve($callerType, $methodCall, $scope);
            }
        }

        return null;
    }
}
