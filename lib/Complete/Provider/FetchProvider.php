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
use DTL\WorseReflection\Node\NodeAndAncestors;
use DTL\WorseReflection\Reflection\ReflectionClass;
use DTL\WorseReflection\Reflection\ReflectionMethod;

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
            $nodePath = new NodeAndAncestors($nodePath);
        }

        $type = $this->typeResolver->resolveParserNode($offset->getFrame(), $nodePath->top());
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
        $this->populateSuggestions($classReflection, $suggestions);
    }

    private function populateSuggestions(ReflectionClass $reflectionClass, Suggestions $suggestions)
    {
        $methods = $reflectionClass->getMethods();

        foreach ($methods as $method) {
            //if ($method->isStatic() && false === $isStaticNode) {
//                continue;
 //           }

 //           if (false === $method->isStatic() && $isStaticNode) {
 //               continue;
 //           }

            $suggestions->add(Suggestion::create(
                $method->getName(),
                Suggestion::TYPE_METHOD,
                $this->formatMethodDoc($method)
            ));
        }

        return $suggestions;

        $classSameInstance = $reflectionClass->getName() == $scope->getClassFqn();

        // inherited properties currently not returned from BR:
        // https://github.com/Roave/BetterReflection/issues/231
        while ($reflectionClass) {
            foreach ($reflectionClass->getProperties() as $property) {
                $scopeIsInstance = $scopeReflection->isSubclassOf($reflectionClass->getName());
                $scopeIsSame = $scopeReflection->getName() === $reflectionClass->getName();

                if ($property->isPrivate() && false === $scopeIsSame) {
                    continue;
                }

                if ($property->isProtected() && (false === $scopeIsSame && false === $scopeIsInstance)) {
                    continue;
                }

                $doc = null;
                if ($property->getDocComment()) {
                    $doc = $this->docBlockFactory->create($property->getDocComment())->getSummary();
                }

                $suggestions->add(Suggestion::create(
                    $property->getName(),
                    Suggestion::TYPE_PROPERTY,
                    $doc
                ));
            }

            $reflectionClass = $reflectionClass->getParentClass();
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
