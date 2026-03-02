<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type as PhpStanType;
use PHPStan\Type\TypeCombinator;

final readonly class ArrayShapeTypeMethodResolver implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private ArrayShapeTypeConstructorResolver $constructorResolver,
    ) {
    }

    public function getClass(): string
    {
        return ArrayShapeType::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['extend', 'omit'], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?PhpStanType {
        $args = $methodCall->getArgs();
        if (!isset($args[0])) {
            return null;
        }

        $callerType = $scope->getType($methodCall->var);
        $existingProperties = $callerType->getTemplateType(ArrayShapeType::class, 'TProperties');
        $existingArrays = $existingProperties->getConstantArrays();
        if ($existingArrays === []) {
            return null;
        }

        $argType = $scope->getType($args[0]->value);

        return match ($methodReflection->getName()) {
            'extend' => $this->resolveExtend($existingArrays, $argType),
            'omit'   => $this->resolveOmit($existingArrays, $argType),
            default  => null,
        };
    }

    /** @param non-empty-list<ConstantArrayType> $existingArrays */
    private function resolveExtend(array $existingArrays, PhpStanType $argType): ?PhpStanType
    {
        $newPropertiesType = $this->constructorResolver->resolvePropertiesArray($argType);
        if ($newPropertiesType === null) {
            return null;
        }

        $newArrays = $newPropertiesType->getConstantArrays();
        if ($newArrays === []) {
            return null;
        }

        $merged = [];
        foreach ($existingArrays as $existing) {
            foreach ($newArrays as $new) {
                $result = $this->mergeConstantArrays($existing, $new);
                if ($result === null) {
                    return null;
                }
                $merged[] = $result;
            }
        }

        return new GenericObjectType(ArrayShapeType::class, [TypeCombinator::union(...$merged)]);
    }

    private function mergeConstantArrays(ConstantArrayType $existing, ConstantArrayType $new): ?PhpStanType
    {
        $newKeyNames = [];
        foreach ($new->getKeyTypes() as $keyType) {
            $strings = $keyType->getConstantStrings();
            if ($strings !== []) {
                $newKeyNames[] = $strings[0]->getValue();
            }
        }

        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($existing->getKeyTypes() as $i => $keyType) {
            $strings = $keyType->getConstantStrings();
            if ($strings === []) {
                return null;
            }
            $keyName = $strings[0]->getValue();
            if (in_array($keyName, $newKeyNames, true)) {
                continue;
            }
            $builder->setOffsetValueType(
                new ConstantStringType($keyName),
                $existing->getValueTypes()[$i],
                $existing->isOptionalKey($i),
            );
        }

        foreach ($new->getKeyTypes() as $i => $keyType) {
            $strings = $keyType->getConstantStrings();
            if ($strings === []) {
                return null;
            }
            $builder->setOffsetValueType(
                new ConstantStringType($strings[0]->getValue()),
                $new->getValueTypes()[$i],
                $new->isOptionalKey($i),
            );
        }

        return $builder->getArray();
    }

    /** @param non-empty-list<ConstantArrayType> $existingArrays */
    private function resolveOmit(array $existingArrays, PhpStanType $argType): ?PhpStanType
    {
        $keysToOmit = $this->extractStringValues($argType);
        if ($keysToOmit === null) {
            return null;
        }

        $filtered = [];
        foreach ($existingArrays as $existing) {
            $builder = ConstantArrayTypeBuilder::createEmpty();
            foreach ($existing->getKeyTypes() as $i => $keyType) {
                $strings = $keyType->getConstantStrings();
                if ($strings === []) {
                    return null;
                }
                $keyName = $strings[0]->getValue();
                if (in_array($keyName, $keysToOmit, true)) {
                    continue;
                }
                $builder->setOffsetValueType(
                    new ConstantStringType($keyName),
                    $existing->getValueTypes()[$i],
                    $existing->isOptionalKey($i),
                );
            }
            $filtered[] = $builder->getArray();
        }

        return new GenericObjectType(ArrayShapeType::class, [TypeCombinator::union(...$filtered)]);
    }

    /** @return list<string>|null */
    private function extractStringValues(PhpStanType $type): ?array
    {
        $constantArrays = $type->getConstantArrays();
        if ($constantArrays === []) {
            return null;
        }

        $result = [];
        foreach ($constantArrays as $constantArray) {
            foreach ($constantArray->getValueTypes() as $valueType) {
                $strings = $valueType->getConstantStrings();
                if ($strings === []) {
                    return null;
                }
                foreach ($strings as $string) {
                    $result[] = $string->getValue();
                }
            }
        }

        return $result;
    }
}
