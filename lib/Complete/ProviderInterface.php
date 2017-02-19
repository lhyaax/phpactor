<?php

namespace Phpactor\Complete;

use Phpactor\Complete\CompleteContext;
use Phpactor\Complete\Suggestions;
use Phpactor\Complete\Scope;
use PhpParser\Node;
use DTL\WorseReflection\Reflection\ReflectionOffset;

interface ProviderInterface
{
    public function canProvideFor(ReflectionOffset $node): bool;

    public function provide(ReflectionOffset $offset, Suggestions $suggestions);
}
