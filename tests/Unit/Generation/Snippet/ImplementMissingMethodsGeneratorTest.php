<?php

namespace Phpactor\Tests\Unit\Generation\Snippet;

use Phpactor\Generation\Snippet\ImplementMissingMethodsGenerator;
use Phpactor\Util\ClassUtil;
use Phpactor\CodeContext;
use DTL\WorseReflection\Reflector;
use DTL\WorseReflection\Reflection\ReflectionClass;
use DTL\WorseReflection\Reflection\ReflectionMethod;
use DTL\WorseReflection\Visibility;
use DTL\WorseReflection\Source;
use DTL\WorseReflection\ClassName;
use DTL\WorseReflection\Reflection\Collection\ReflectionMethodCollection;

class ImplementMissingMethodsGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImplementMissingMethodsGenerator
     */
    private $generator;

    /**
     * @var ClassReflector
     */
    private $reflector;

    /**
     * @var ClassUtil
     */
    private $classUtil;

    /**
     * @var ReflectionClass
     */
    private $interfaceReflection;

    /**
     * @var ReflectionClass
     */
    private $classReflection;

    public function setUp()
    {
        $this->reflector = $this->prophesize(Reflector::class);
        $this->classUtil = $this->prophesize(ClassUtil::class);
        $this->generator = new ImplementMissingMethodsGenerator(
            $this->reflector->reveal(),
            $this->classUtil->reveal()
        );

        $this->classReflection = $this->prophesize(ReflectionClass::class);
        $this->classReflection->isInterface()->willReturn(false);
        $this->interfaceReflection = $this->prophesize(ReflectionClass::class);
        $this->interfaceReflection->isInterface()->willReturn(true);
    }

    /**
     * It should add any missing contracted methods.
     * 
     * @dataProvider provideAddMissing
     */
    public function testAddMissing(array $methodConfigs, string $expected)
    {
        $source = Source::fromString('asd');
        $className = ClassName::fromString('FooClass');
        $this->classUtil->getClassNameFromSource($source)->willReturn($className);
        $this->reflector->reflectClass($className)->willReturn(
            $this->classReflection->reveal()
        );

        $methods = [];
        foreach ($methodConfigs as $methodName => $methodConfig) {
            $methods[] = $this->createMethod($methodName, $methodConfig)->reveal();
        }

        $this->classReflection->getMethods()->willReturn(new ReflectionMethodCollection('method', $methods));

        $snippet = $this->generator->generate(CodeContext::create($source, 1234), []);

        $this->assertEquals($expected . PHP_EOL, $snippet);
    }

    public function provideAddMissing()
    {
        return [
            [
                [
                    'abstractMethod' => [ 'is_abstract' => true ],
                ],
                <<<'EOT'
/**
 * {@inheritDoc}
 */
public function abstractMethod()
{
}

EOT
            ],
            [
                [
                    'interfaceMethod' => [ 'is_interface' => true ],
                ],
                <<<'EOT'
/**
 * {@inheritDoc}
 */
public function interfaceMethod()
{
}

EOT
            ],
            [
                [
                    'protected' => [ 'is_interface' => true, 'is_protected' => true ],
                ],
                <<<'EOT'
/**
 * {@inheritDoc}
 */
protected function protected()
{
}

EOT
            ],
            [
                [
                    'concrete' => [  ],
                    'interface' => [ 'is_interface' => true ],
                ],
                <<<'EOT'
/**
 * {@inheritDoc}
 */
public function interface()
{
}

EOT
            ],
        ];
    }

    private function createMethod(string $name, array $options = [])
    {
        $options = array_merge([
            'is_abstract' => false,
            'is_interface' => false,
            'is_protected' => false,
            'parameters' => [],
        ], $options);

        $method = $this->prophesize(ReflectionMethod::class);

        $visibility = Visibility::public();

        if ($options['is_protected']) {
            $visibility = Visibility::protected();
        }

        $method->getVisibility()->willReturn($visibility);
        $method->isAbstract()->willReturn($options['is_abstract']);
        $method->getName()->willReturn($name);
        $method->getDeclaringClass()->willReturn(
            $options['is_interface'] ? $this->interfaceReflection->reveal() : $this->classReflection->reveal()
        );
        $method->getParameters()->willReturn($options['parameters']);

        return $method;
    }


}
