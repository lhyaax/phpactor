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

class FetchProvider implements ProviderInterface
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
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
        $node = $offset->getNode();

        if (
            $node instanceof Expr\PropertyFetch ||
            $node instanceof Expr\MethodCall
        ) {
            // knock off the fake "completion" node
            $node = $scope->getNode()->var;
        }

        $type = $offset->getType();
        $classReflection = null;

        if ($type === Type::class) {
            $classReflection = $this->reflector->reflectClass(
                $node->getType()->getClassName()
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

        $scopeReflection = $this->reflector->reflect($scope->getClassFqn());

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

        if ($method->getDocComment()) {
            $docObject = $this->docBlockFactory->create($method->getDocComment());

            return $doc . PHP_EOL . '    ' . $docObject->getSummary();
        }

        return $doc;
    }


    private function reflectionTypeFromName(ReflectionClass $reflectionClass, string $name)
    {
        // TODO: Refactor this
        $type = (new FindTypeFromAst())->__invoke(
            $name,
            $reflectionClass->getLocatedSource(),
            $reflectionClass->getNamespaceName()
        );

        if (false === $type instanceof Object_) {
            return;
        }

        return $this->reflector->reflect($type->getFqsen());
    }
}
