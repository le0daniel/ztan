<?php declare(strict_types=1);

namespace Le0daniel\Ztan\PhpStan\Rules;

use Le0daniel\Ztan\Types\Complex\ArrayShapeType;
use Le0daniel\Ztan\Types\Complex\ObjectShapeType;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
final readonly class ExtendOmitArgumentRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!in_array($methodName, ['extend', 'omit'], true)) {
            return [];
        }

        $callerType = $scope->getType($node->var);
        $classNames = $callerType->getObjectClassNames();
        $isArrayShape = in_array(ArrayShapeType::class, $classNames, true);
        $isObjectShape = in_array(ObjectShapeType::class, $classNames, true);

        if (!$isArrayShape && !$isObjectShape) {
            return [];
        }

        $args = $node->getArgs();
        if (!isset($args[0])) {
            return [];
        }

        $argType = $scope->getType($args[0]->value);

        if ($argType->getConstantArrays() === []) {
            $className = $isArrayShape ? 'ArrayShapeType' : 'ObjectShapeType';
            return [
                RuleErrorBuilder::message(
                    sprintf('Call to %s::%s() requires a literal array argument.', $className, $methodName),
                )
                    ->identifier('le0daniel.ztan.extendOmitLiteralArray')
                    ->build(),
            ];
        }

        if ($methodName === 'omit') {
            foreach ($argType->getConstantArrays() as $constantArray) {
                foreach ($constantArray->getValueTypes() as $valueType) {
                    if ($valueType->getConstantStrings() === []) {
                        $className = $isArrayShape ? 'ArrayShapeType' : 'ObjectShapeType';
                        return [
                            RuleErrorBuilder::message(
                                sprintf(
                                    'Call to %s::omit() requires a literal array of strings.',
                                    $className,
                                ),
                            )
                                ->identifier('le0daniel.ztan.extendOmitLiteralArray')
                                ->build(),
                        ];
                    }
                }
            }
        }

        return [];
    }
}
