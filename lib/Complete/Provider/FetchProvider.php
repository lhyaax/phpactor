<?php

namespace Phpactor\Complete\Provider;

use PhpParser\Node\Expr;
use Phpactor\Complete\ProviderInterface;
use Phpactor\Complete\Suggestions;
use PhpParser\NodeAbstract;
use Phpactor\Complete\Scope;
use Phpactor\Complete\Suggestion;
use DTL\WorseReflection\Reflection\ReflectionOffset;
use DTL\WorseReflection\Type;
use PhpParser\Node;
use DTL\WorseReflection\Reflector;
use DTL\WorseReflection\TypeResolver;
use DTL\WorseReflection\Node\NodePath;
use DTL\WorseReflection\Reflection\ReflectionClass;
use DTL\WorseReflection\Reflection\ReflectionMethod;
use DTL\WorseReflection\Visibility;
use DTL\WorseReflection\ClassName;
use PhpParser\Node\Stmt\ClassLike;

class FetchProvider implements ProviderInterface
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
        $this->typeResolver = new TypeResolver($reflector);
    }

    public function canProvideFor(ReflectionOffset $offset): bool
    {
        $node = $offset->getNode()->top();

        return 
            $node instanceof Expr\ClassConstFetch ||
            $node instanceof Expr\PropertyFetch  || 
            $node instanceof Expr\StaticCall ||
            $node instanceof Expr\MethodCall;
    }

    public function provide(ReflectionOffset $offset, Suggestions $suggestions)
    {
        $nodePath = $offset->getNode();
        $node = $nodePath->top();

        if (
            $node instanceof Expr\PropertyFetch ||
            $node instanceof Expr\MethodCall
        ) {
            $topNode = $nodePath->pop()->var;
            $nodePath = $nodePath->all();
            $nodePath[] = $topNode;
            $nodePath = new NodePath($nodePath);
        }

        // oh jesus ...
        // use class of the const fetch
        if ($node instanceof Expr\ClassConstFetch) {
            $className = ClassName::fromParts($node->class->parts);
            $className = $offset->getFrame()->getSourceContext()->resolveClassName($className);

            if ($className->getShortName() === 'self') {
                $classNode = $nodePath->seekBack(function ($node) {
                    if ($node instanceof ClassLike) {
                        return true;
                    }

                    return false;
                });

                $className = ClassName::fromString($classNode->name);
            } else {
                $className = ClassName::fromParts($node->class->parts);
            }
            
            $className = $offset->getFrame()->getSourceContext()->resolveClassName($className);

            $type = Type::class($className);
        } else {
            $type = $this->typeResolver->resolveParserNode($offset->getFrame(), $nodePath->top());
        }
        $classReflection = null;

        if ($type->isClass()) {
            $classReflection = $this->reflector->reflectClass(
                $type->getClassName()
            );
        }

        if (null === $classReflection) { 
            return;
        }

        // populate the suggestions with the classes members.
        $this->populateSuggestions($nodePath->top(), $classReflection, $suggestions);
    }

    private function populateSuggestions(Node $node, ReflectionClass $reflectionClass, Suggestions $suggestions)
    {
        foreach ($reflectionClass->getVisibleProperties() as $property) {
            $suggestions->add(Suggestion::create(
                $property->getName(),
                Suggestion::TYPE_PROPERTY,
                null // $doc
            ));
        }

        $originalReflectionClass = $reflectionClass;
        while ($reflectionClass) {

            $methods = $reflectionClass->getMethods();
            $isStaticNode = $node instanceof Expr\ClassConstFetch;

            foreach ($methods as $method) {
                if ($method->isStatic() && false === $isStaticNode) {
                    continue;
                }

                if (false === $method->isStatic() && $isStaticNode) {
                    continue;
                }

                $suggestions->add(Suggestion::create(
                    $method->getName(),
                    Suggestion::TYPE_METHOD,
                    $this->formatMethodDoc($method)
                ));
            }

            $reflectionClass = $reflectionClass->hasParentClass() ? $reflectionClass->getParentClass() : null;
        }

    }

    /**
     * TODO: move this to formatting class
     */
    private function formatMethodDoc(ReflectionMethod $method)
    {
        $parts = [];
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getType()) {
                $type = $parameter->getType();
                $typeString = (string) $type;
                $parts[] = sprintf('%s $%s', $typeString, $parameter->getName());
                continue;
            }

            $parts[] = '$' . $parameter->getName();
        }


        $doc = $method->getName() . '(' . implode(', ', $parts) . '): ' . (string) $method->getReturnType();

        return $doc;
    }
}
