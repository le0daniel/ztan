<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan\Resolvers;

use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Types\TransformType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type as PhpStanType;

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

        $innerType = $scope->getType($args[0]->value);
        $resolvedTValue = $innerType->getTemplateType(Type::class, 'TValue');

        $closureNode = $args[1]->value;
        if (!$closureNode instanceof Expr\Closure && !$closureNode instanceof Expr\ArrowFunction) {
            return null;
        }

        $returnType = $this->resolveClosureReturnType($closureNode, $resolvedTValue, $scope);
        if ($returnType === null) {
            return null;
        }

        return new GenericObjectType(TransformType::class, [$resolvedTValue, $returnType]);
    }

    private function resolveClosureReturnType(
        Expr\Closure|Expr\ArrowFunction $closureNode,
        PhpStanType $parameterType,
        Scope $scope,
    ): ?PhpStanType {
        if ($closureNode instanceof Expr\ArrowFunction) {
            return $this->evaluateExpr($closureNode->expr, $parameterType, $scope);
        }

        $returnExprs = [];
        foreach ($closureNode->stmts ?? [] as $stmt) {
            if ($stmt instanceof Return_ && $stmt->expr !== null) {
                $returnExprs[] = $stmt->expr;
            }
        }

        if (count($returnExprs) !== 1) {
            return null;
        }

        return $this->evaluateExpr($returnExprs[0], $parameterType, $scope);
    }

    private function evaluateExpr(Expr $expr, PhpStanType $parameterType, Scope $scope): ?PhpStanType
    {
        // $paramName (first closure parameter) → resolved type
        if ($expr instanceof Expr\Variable && is_string($expr->name)) {
            // Any variable that isn't the closure param can be resolved by scope
            // We assume the first closure param maps to our resolved type
            return $parameterType;
        }

        // $var['key'] → offset access on resolved type
        if ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
            $varType = $this->evaluateExpr($expr->var, $parameterType, $scope);
            if ($varType === null) {
                return null;
            }
            $dimType = $scope->getType($expr->dim);
            return $varType->getOffsetValueType($dimType);
        }

        // ['key' => expr, ...] → build constant array
        if ($expr instanceof Expr\Array_) {
            $builder = ConstantArrayTypeBuilder::createEmpty();
            foreach ($expr->items as $item) {
                $valueType = $this->evaluateExpr($item->value, $parameterType, $scope);
                if ($valueType === null) {
                    return null;
                }

                if ($item->key !== null) {
                    $keyType = $scope->getType($item->key);
                    $builder->setOffsetValueType($keyType, $valueType);
                } else {
                    $builder->setOffsetValueType(null, $valueType);
                }
            }
            return $builder->getArray();
        }

        // For any other expression, use the scope's type resolution
        // This works for constants, function calls, etc. that don't depend on the parameter
        return $scope->getType($expr);
    }
}
