<?php

namespace Phpactor\Util;

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use BetterReflection\SourceLocator\Type\StringSourceLocator;
use DTL\WorseReflection\ClassName;
use DTL\WorseReflection\Source;

class ClassUtil
{
    public function getClassNameFromFile(string $file): ClassName
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator($file));

        $classes = $reflector->getAllClasses();

        if (empty($classes)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find a class in "%s"', $file
            ));
        }

        $class = reset($classes);

        return $class->getName();
    }

    public function getClassNameFromSource(Source $source): ClassName
    {
        $reflector = new ClassReflector(new StringSourceLocator($source->getSource()));

        $classes = $reflector->getAllClasses();

        if (empty($classes)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find a class in "%s"', $file
            ));
        }

        $class = reset($classes);

        return $class->getName();
    }
}
