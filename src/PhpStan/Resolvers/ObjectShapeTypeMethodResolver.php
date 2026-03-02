<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Resolvers;

use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectShapeType as PhpStanObjectShapeType;
use PHPStan\Type\Type as PhpStanType;

final readonly class ObjectShapeTypeMethodResolver implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private ObjectShapeTypeConstructorResolver $constructorResolver,
    ) {
    }

    public function getClass(): string
    {
        return ObjectShapeType::class;
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
        $existingProperties = $callerType->getTemplateType(ObjectShapeType::class, 'TProperties');
        if (!$existingProperties instanceof PhpStanObjectShapeType) { // @phpstan-ignore phpstanApi.instanceofType
            return null;
        }

        $argType = $scope->getType($args[0]->value);

        return match ($methodReflection->getName()) {
            'extend' => $this->resolveExtend($existingProperties, $argType),
            'omit'   => $this->resolveOmit($existingProperties, $argType),
            default  => null,
        };
    }

    private function resolveExtend(PhpStanObjectShapeType $existing, PhpStanType $argType): ?PhpStanType
    {
        $newType = $this->constructorResolver->resolvePropertiesObject($argType);
        if (!$newType instanceof PhpStanObjectShapeType) { // @phpstan-ignore phpstanApi.instanceofType
            return null;
        }

        $mergedProperties = array_merge($existing->getProperties(), $newType->getProperties());

        $newPropertyKeys = array_keys($newType->getProperties());
        $existingOptionals = array_values(array_filter(
            $existing->getOptionalProperties(),
            static fn(int|string $key): bool => !in_array($key, $newPropertyKeys, true),
        ));
        $mergedOptionals = [...$existingOptionals, ...$newType->getOptionalProperties()];

        return new GenericObjectType(
            ObjectShapeType::class,
            [new PhpStanObjectShapeType($mergedProperties, $mergedOptionals)],
        );
    }

    private function resolveOmit(PhpStanObjectShapeType $existing, PhpStanType $argType): ?PhpStanType
    {
        $keysToOmit = $this->extractStringValues($argType);
        if ($keysToOmit === null) {
            return null;
        }

        $filteredProperties = array_filter(
            $existing->getProperties(),
            static fn(int|string $key): bool => !in_array($key, $keysToOmit, true),
            ARRAY_FILTER_USE_KEY,
        );

        $filteredOptionals = array_values(array_filter(
            $existing->getOptionalProperties(),
            static fn(int|string $key): bool => !in_array($key, $keysToOmit, true),
        ));

        return new GenericObjectType(
            ObjectShapeType::class,
            [new PhpStanObjectShapeType($filteredProperties, $filteredOptionals)],
        );
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
