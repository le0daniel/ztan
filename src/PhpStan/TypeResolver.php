<?php declare(strict_types=1);

namespace Le0daniel\Assertions\PhpStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

interface TypeResolver
{
    public function getTargetClass(): string;

    public function resolve(Type $callerType, MethodCall $methodCall, Scope $scope): ?Type;
}
