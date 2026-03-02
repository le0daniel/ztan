<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Contracts\Type;
use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectShapeType as PhpStanObjectShapeType;
use PHPStan\Type\Type as PhpStanType;
use PHPStan\Type\TypeCombinator;

final readonly class ObjectShapeTypeConstructorResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return ObjectShapeType::class;
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
        $resolvedShape = $this->resolvePropertiesObject($propertiesType);
        if ($resolvedShape === null) {
            return null;
        }

        return new GenericObjectType(ObjectShapeType::class, [$resolvedShape]);
    }

    public function resolvePropertiesObject(PhpStanType $propertiesType): ?PhpStanType
    {
        $constantArrays = $propertiesType->getConstantArrays();
        if ($constantArrays === []) {
            return null;
        }

        $resolvedShapes = [];

        foreach ($constantArrays as $constantArray) {
            $properties = [];
            $optionalProperties = [];
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

                $properties[$cleanKey] = $resolvedValueType;
                if ($isOptional) {
                    $optionalProperties[] = $cleanKey;
                }
            }

            $resolvedShapes[] = new PhpStanObjectShapeType($properties, $optionalProperties);
        }

        return TypeCombinator::union(...$resolvedShapes);
    }

    private function resolveValueOutputType(PhpStanType $valueType): PhpStanType
    {
        if (in_array(ObjectShapeType::class, $valueType->getObjectClassNames(), true)) {
            $nested = $this->resolvePropertiesFromObjectShapeGeneric($valueType);
            if ($nested !== null) {
                return $nested;
            }
        }

        if (in_array(ArrayShapeType::class, $valueType->getObjectClassNames(), true)) {
            $nested = $this->resolvePropertiesFromArrayShapeGeneric($valueType);
            if ($nested !== null) {
                return $nested;
            }
        }

        return $valueType->getTemplateType(Type::class, 'TValue');
    }

    private function resolvePropertiesFromObjectShapeGeneric(PhpStanType $callerType): ?PhpStanType
    {
        $propertiesType = $callerType->getTemplateType(ObjectShapeType::class, 'TProperties');
        if (!$propertiesType instanceof PhpStanObjectShapeType) { // @phpstan-ignore phpstanApi.instanceofType
            return null;
        }

        return $propertiesType;
    }

    private function resolvePropertiesFromArrayShapeGeneric(PhpStanType $callerType): ?PhpStanType
    {
        $propertiesType = $callerType->getTemplateType(ArrayShapeType::class, 'TProperties');
        if ($propertiesType->getConstantArrays() === []) {
            return null;
        }

        return $propertiesType;
    }
}
