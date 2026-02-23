<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan\Resolvers;

use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Types\Complex\TupleType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type as PhpStanType;

final readonly class TupleTypeConstructorResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return TupleType::class;
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

        $builder = ConstantArrayTypeBuilder::createEmpty();
        foreach ($args as $i => $arg) {
            $argType = $scope->getType($arg->value);
            $valueType = $argType->getTemplateType(Type::class, 'TValue');
            $builder->setOffsetValueType(
                new ConstantIntegerType($i),
                $valueType,
            );
        }

        $tupleArrayType = $builder->getArray();

        return new GenericObjectType(TupleType::class, [$tupleArrayType]);
    }
}
