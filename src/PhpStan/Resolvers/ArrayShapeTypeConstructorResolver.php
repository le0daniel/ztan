<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan\Resolvers;

use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Types\Complex\ArrayShapeType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type as PhpStanType;
use PHPStan\Type\TypeCombinator;

final readonly class ArrayShapeTypeConstructorResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return ArrayShapeType::class;
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

        $propertiesType = $scope->getType($args[0]->value);
        $resolvedShape = $this->resolvePropertiesArray($propertiesType);
        if ($resolvedShape === null) {
            return null;
        }

        return new GenericObjectType(ArrayShapeType::class, [$resolvedShape]);
    }

    private function resolvePropertiesArray(PhpStanType $propertiesType): ?PhpStanType
    {
        $constantArrays = $propertiesType->getConstantArrays();
        if ($constantArrays === []) {
            return null;
        }

        $resolvedArrays = [];

        foreach ($constantArrays as $constantArray) {
            $builder = ConstantArrayTypeBuilder::createEmpty();
            $keyTypes = $constantArray->getKeyTypes();
            $valueTypes = $constantArray->getValueTypes();

            foreach ($keyTypes as $index => $keyType) {
                $constantStrings = $keyType->getConstantStrings();
                if ($constantStrings === []) {
                    return null;
                }

                $rawKey = $constantStrings[0]->getValue();
                $isOptional = str_ends_with($rawKey, '?');
                $cleanKey = $isOptional ? substr($rawKey, 0, -1) : $rawKey;

                $valueType = $valueTypes[$index];
                $resolvedValueType = $this->resolveValueOutputType($valueType);

                $builder->setOffsetValueType(
                    new ConstantStringType($cleanKey),
                    $resolvedValueType,
                    $isOptional,
                );
            }

            $resolvedArrays[] = $builder->getArray();
        }

        return TypeCombinator::union(...$resolvedArrays);
    }

    private function resolveValueOutputType(PhpStanType $valueType): PhpStanType
    {
        if (in_array(ArrayShapeType::class, $valueType->getObjectClassNames(), true)) {
            $nested = $this->resolvePropertiesFromGeneric($valueType);
            if ($nested !== null) {
                return $nested;
            }
        }

        return $valueType->getTemplateType(Type::class, 'TValue');
    }

    private function resolvePropertiesFromGeneric(PhpStanType $callerType): ?PhpStanType
    {
        $propertiesType = $callerType->getTemplateType(ArrayShapeType::class, 'TProperties');
        if ($propertiesType->getConstantArrays() === []) {
            return null;
        }

        return $propertiesType;
    }
}
