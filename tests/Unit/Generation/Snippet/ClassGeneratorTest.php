<?php

namespace Phpactor\Tests\Unit\Generation\Snippet;

use Phpactor\Composer\ClassNameResolver;
use Phpactor\CodeContext;
use Phpactor\Generation\Snippet\ClassGenerator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use DTL\WorseReflection\Location;
use DTL\WorseReflection\Source;
use DTL\WorseReflection\ClassName;

class ClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassNameResolver
     */
    private $resolver;

    /**
     * @var ClassGenerator
     */
    private $generator;

    public function setUp()
    {
        $this->resolver = $this->prophesize(ClassNameResolver::class);
        $this->generator = new ClassGenerator($this->resolver->reveal());
    }

    /**
     * @dataProvider provideGenerate
     */
    public function testGenerate(array $options, $expectedSnippet)
    {
        $source = Source::fromString('');
        $resolver = new OptionsResolver();
        $this->generator->configureOptions($resolver);
        $options = $resolver->resolve($options);
        $this->resolver->resolve($source->getLocation())->willReturn(
            ClassName::fromString('Foo\\Bar')
        );

        $snippet = $this->generator->generate(CodeContext::create($source, 0), $options);
        $this->assertEquals($expectedSnippet, $snippet);
    }

    public function provideGenerate()
    {
        return [
            [
                [
                ],
                <<<EOT
<?php

namespace Foo;

class Bar
{
}
EOT
            ],
            [
                [
                    'type' => 'class',
                ],
                <<<EOT
<?php

namespace Foo;

class Bar
{
}
EOT
            ],
            [
                [
                    'type' => 'trait',
                ],
                <<<EOT
<?php

namespace Foo;

trait Bar
{
}
EOT
            ],
            [
                [
                    'type' => 'interface',
                ],
                <<<EOT
<?php

namespace Foo;

interface Bar
{
}
EOT
            ],
        ];
    }
}
