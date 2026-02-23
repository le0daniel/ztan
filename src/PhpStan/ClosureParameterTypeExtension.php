<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan;

use Le0daniel\Assertions\Contracts\Type;
use Le0daniel\Assertions\Types\RefineType;
use Le0daniel\Assertions\Types\TransformType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\Type as PhpStanType;

final readonly class ClosureParameterTypeExtension implements StaticMethodParameterClosureTypeExtension
{
    /** @var array<class-string, string> Maps class name to closure parameter name */
    private const CLOSURE_PARAMS = [
        TransformType::class => 'transformFn',
        RefineType::class => 'refiner',
    ];

    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        if ($methodReflection->getName() !== '__construct') {
            return false;
        }

        $className = $methodReflection->getDeclaringClass()->getName();

        return isset(self::CLOSURE_PARAMS[$className])
            && $parameter->getName() === self::CLOSURE_PARAMS[$className];
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): ?PhpStanType {
        $args = $methodCall->getArgs();
        if (!isset($args[0])) {
            return null;
        }

        $innerType = $scope->getType($args[0]->value);
        $resolvedValueType = $innerType->getTemplateType(Type::class, 'TValue');

        return new ClosureType(
            [new NativeParameterReflection( // @phpstan-ignore phpstanApi.constructor
                'value',
                false,
                $resolvedValueType,
                PassedByReference::createNo(),
                false,
                null,
            )],
            new MixedType(),
        );
    }
}
