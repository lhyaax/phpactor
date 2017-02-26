<?php

namespace Phpactor\Generation\Snippet;

use Phpactor\Generation\SnippetGeneratorInterface;
use Composer\Autoload\ClassLoader;
use Phpactor\CodeContext;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Phpactor\Composer\ClassNameResolver;
use DTL\WorseReflection\ClassName;

class ClassGenerator implements SnippetGeneratorInterface
{
    /**
     * @var ClassLoader
     */
    private $resolver;

    public function __construct(ClassNameResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function generate(CodeContext $codeContext, array $options): string
    {
        $classFqn = $this->resolver->resolve($codeContext->getSource()->getLocation());

        return $this->createSnippet($classFqn, $options['type']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('type', 'class');
        $resolver->setAllowedValues('type', [ 'class', 'trait', 'interface' ]);
    }

    private function createSnippet(ClassName $fqn, string $type)
    {
        $snippet = [];
        $snippet[] = '<?php';
        $snippet[] = '';
        $snippet[] = 'namespace ' . $fqn->getNamespaceName()->getFqn() . ';';
        $snippet[] = '';
        $snippet[] = sprintf('%s %s', $type, $fqn->getShortName());
        $snippet[] = '{';
        $snippet[] = '}';

        return implode(PHP_EOL, $snippet);
    }
}
