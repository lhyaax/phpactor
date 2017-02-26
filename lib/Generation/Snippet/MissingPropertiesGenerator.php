<?php

namespace Phpactor\Generation\Snippet;

use BetterReflection\Reflector\ClassReflector;
use Phpactor\CodeContext;
use Phpactor\Util\ClassUtil;
use Phpactor\Generation\SnippetGeneratorInterface;
use PhpParser\NodeTraverser;
use Phpactor\AstVisitor\AssignedPropertiesVisitor;
use PhpParser\Node;
use BetterReflection\Util\Visitor\VariableCollectionVisitor;
use BetterReflection\NodeCompiler\CompilerContext;
use BetterReflection\Reflection\ReflectionClass;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar\DNumber;
use Symfony\Component\OptionsResolver\OptionsResolver;
use DTL\WorseReflection\Reflector;

class MissingPropertiesGenerator implements SnippetGeneratorInterface
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    /**
     * @var ClassUtil
     */
    private $classUtil;

    /**
     * @var AssignedPropertiesVisitor
     */
    private $assignedPropertiesVisitor;

    public function __construct(
        Reflector $reflector,
        ClassUtil $classUtil,
        AssignedPropertiesVisitor $assignedPropertiesVisitor = null
    )
    {
        $this->reflector = $reflector;
        $this->classUtil = $classUtil;
        $this->assignedPropertiesVisitor = $assignedPropertiesVisitor ?: new AssignedPropertiesVisitor();
    }

    public function generate(CodeContext $codeContext, array $options): string
    {
        $reflection = $this->reflector->reflectClass(
            $this->classUtil->getClassNameFromSource($codeContext->getSource())
        );

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($propertyVisitor = $this->assignedPropertiesVisitor);
        $nodeTraverser->traverse([$reflection->getAst()]);

        $missingProperties = $this->resolveMissingProperties($propertyVisitor, $reflection);

        $snippet = [];

        foreach ($missingProperties as $missingProperty) {
            $snippet[] = '/**';
            $snippet[] = ' * @var ' . ((string) $missingProperty->getType() ?: 'mixed');
            $snippet[] = ' */';
            $snippet[] = sprintf(
                'private $%s;' . PHP_EOL,
                $missingProperty->var->name
            );
        }

        return implode(PHP_EOL, $snippet);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    private function resolveMissingProperties(AssignedPropertiesVisitor $visitor, ReflectionClass $reflection)
    {

        $assigned = $visitor->getAssignedPropertyNodes();
        $properties = $reflection->getProperties();

        return array_filter($assigned, function (Node $property) use ($properties) {
            return !array_key_exists($property->var->name, $properties);
        });
    }
}
