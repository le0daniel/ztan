<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan;

use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

/**
 * PHPStan generalizes a template it infers from an argument unless the template is
 * covariant: 'a' becomes string, array{type: 'a'} becomes array{type: string}. Method
 * templates can't be covariant and BaseType's TValue is invariant, so every schema
 * built by wrapping another one through inference (Ztan::list(), ->pipe(), new
 * NullableType(), ...) would lose its literals, and with them the discriminator
 * narrowing of a nested discriminatedUnion.
 *
 * These helpers resolve the call from the inferred template map instead, which PHPStan
 * exposes before generalizing.
 */
final class InferredTemplates
{
    private function __construct() {}

    /**
     * The declared return type of $method with its templates bound to the exact
     * inferred argument types.
     *
     * @param array<Arg> $args
     */
    public static function returnType(MethodReflection $method, array $args, Scope $scope): ?Type
    {
        $variants = $method->getVariants();
        if (count($variants) !== 1) {
            return null;
        }

        $inferred = self::infer($method, $args, $scope);
        if ($inferred === null) {
            return null;
        }

        $unresolved = false;
        $returnType = TypeTraverser::map(
            $variants[0]->getReturnType(),
            static function (Type $type, callable $traverse) use ($inferred, &$unresolved): Type {
                if (!$type instanceof TemplateType) {
                    return $traverse($type);
                }

                $resolved = $inferred->getType($type->getName());
                if ($resolved === null) {
                    $unresolved = true;
                    return $type;
                }

                return $resolved;
            },
        );

        return $unresolved ? null : $returnType;
    }

    /**
     * The generic type `new Class(...$args)` produces, with the class templates bound
     * to the exact inferred argument types.
     *
     * @param array<Arg> $args
     */
    public static function instantiation(MethodReflection $constructor, array $args, Scope $scope): ?Type
    {
        $class = $constructor->getDeclaringClass();
        $inferred = self::infer($constructor, $args, $scope);
        if ($inferred === null) {
            return null;
        }

        foreach (array_keys($class->getTemplateTypeMap()->getTypes()) as $name) {
            if ($inferred->getType($name) === null) {
                return null;
            }
        }

        return new GenericObjectType($class->getName(), $class->typeMapToList($inferred));
    }

    /**
     * @param array<Arg> $args
     */
    private static function infer(MethodReflection $method, array $args, Scope $scope): ?TemplateTypeMap
    {
        // PHPStan hands extensions already-normalized (positional) arguments
        $variant = ParametersAcceptorSelector::selectFromArgs($scope, $args, $method->getVariants());

        $inferred = $variant->getResolvedTemplateTypeMap();
        foreach ($inferred->getTypes() as $type) {
            if ($type instanceof ErrorType) {
                return null;
            }
        }

        return $inferred;
    }
}
