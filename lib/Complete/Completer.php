<?php

namespace Phpactor\Complete;

use Phpactor\Reflection\ReflectorInterface;
use Phpactor\Complete\Provider\VariableProvider;
use Phpactor\Complete\ScopeResolver;
use Phpactor\Complete\ScopeFactory;
use Phpactor\Complete\Suggestions;
use Phpactor\CodeContext;
use DTL\WorseReflection\Reflector;

class Completer
{
    /**
     * @var ProviderInterface
     */
    private $providers = [];

    /**
     * @var ScopeFactory
     */
    private $reflector;

    public function __construct(Reflector $reflector, array $providers)
    {
        $this->providers = $providers;
        $this->reflector = $reflector;
    }

    public function complete(CodeContext $codeContext): Suggestions
    {
        $suggestions = new Suggestions();

        $offset = $this->reflector->reflectOffsetFromSource(
            Source::fromString($codeContext->getSource()),
            $codeContext->getOffset()
        );

        if (false === $offset->hasNode()) {
            return $suggestions;
        }

        foreach ($this->providers as $provider) {
            if (false === $provider->canProvideFor($offset->getNode())) {
                continue;
            }

            $provider->provide($offset, $suggestions);
        }

        return $suggestions;
    }
}
