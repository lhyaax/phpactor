<?php

namespace Phpactor\Complete\Provider;

use PhpParser\Node;
use Phpactor\Complete\CompleteContext;
use PhpParser\Node\Stmt;
use Phpactor\Complete\Scope;
use BetterReflection\Reflector\Reflector;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use Phpactor\Complete\Suggestions;
use Phpactor\Complete\ProviderInterface;
use Phpactor\Complete\Suggestion;
use PhpParser\Node\Stmt\ClassMethod;
use DTL\WorseReflection\Reflection\ReflectionOffset;

class VariableProvider implements ProviderInterface
{
    public function canProvideFor(ReflectionOffset $offset): bool
    {
        $node = $offset->getNode();

        return $node instanceof Variable || $node instanceof ClassMethod;
    }

    public function provide(ReflectionOffset $offset, Suggestions $suggestions)
    {
        foreach ($frame->all() as $name => $type) {
            $suggestions->add(Suggestion::create('$' . $name, Suggestion::TYPE_VARIABLE));
        }

        $this->provideSuperGlobals($suggestions);

        // TODO: Function scope
        // TODO: Closure scope
    }

    private function provideSuperGlobals(Suggestions $suggestions)
    {
        foreach ([
            '$GLOBALS',
            '$_SERVER',
            '$_GET',
            '$_POST',
            '$_FILES',
            '$_COOKIE',
            '$_SESSION',
            '$_REQUEST',
            '$_ENV'
        ] as $superGlobal) {
            $suggestions->add(Suggestion::create($superGlobal, Suggestion::TYPE_VARIABLE, '*superglobal*'));
        }
    }
}
