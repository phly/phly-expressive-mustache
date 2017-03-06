<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace PhlyTest\Expressive\Mustache;

use ArrayObject;
use Phly\Expressive\Mustache\MustacheTemplate;
use Phly\Mustache\Mustache;
use Phly\Mustache\Resolver\AggregateResolver;
use Phly\Mustache\Resolver\DefaultResolver;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Template\TemplateRendererInterface;

class MustacheTemplateTest extends TestCase
{
    public function foo()
    {
        $this->mustache = $this->prophesize(Mustache::class);
    }

    public function createTemplate()
    {
        return new MustacheTemplate($this->mustache->reveal());
    }

    public function testUsesComposedDefaultResolverWhenPresentInAggregate()
    {
        $mustache = new Mustache();
        $resolver = $mustache->getResolver();
        $this->assertInstanceOf(AggregateResolver::class, $resolver);
        $this->assertTrue($resolver->hasType(DefaultResolver::class));
        $defaultResolver = $resolver->fetchByType(DefaultResolver::class);

        $template = new MustacheTemplate($mustache);
        $this->assertAttributeSame($defaultResolver, 'resolver', $template);
    }

    public function testInjectsADefaultResolverInAggregateIfNoneFound()
    {
        $aggregate = new AggregateResolver();
        $this->assertFalse($aggregate->hasType(DefaultResolver::class));
        $mustache  = new Mustache($aggregate);

        $template = new MustacheTemplate($mustache);
        $this->assertTrue($aggregate->hasType(DefaultResolver::class));
    }

    public function testUsesFirstDefaultResolverEncounteredIfMultipleAreFound()
    {
        $aggregate = new AggregateResolver();
        $this->assertFalse($aggregate->hasType(DefaultResolver::class));
        $expected = new DefaultResolver();
        $aggregate->attach($expected);
        $aggregate->attach(clone $expected);
        $aggregate->attach(clone $expected);

        $mustache  = new Mustache($aggregate);

        $template = new MustacheTemplate($mustache);
        $this->assertTrue($aggregate->hasType(DefaultResolver::class));
        $this->assertAttributeSame($expected, 'resolver', $template);
    }

    public function testAddPathProxiesToDefaultResolver()
    {
        $resolver = $this->prophesize(DefaultResolver::class);
        $resolver->addTemplatePath('foo/bar/', 'foo')->shouldBeCalled();
        $aggregate = new AggregateResolver();
        $aggregate->attach($resolver->reveal());
        $mustache = new Mustache($aggregate);

        $template = new MustacheTemplate($mustache);
        $this->assertNull($template->addPath('foo/bar/', 'foo'));
    }

    public function testGetPathsPullsPathsByNamespaceFromDefaultResolver()
    {
        $namespaces = [
            'foo' => [uniqid(), uniqid()],
            'bar' => [uniqid(), uniqid()],
        ];
        $resolver = $this->prophesize(DefaultResolver::class);
        $resolver->getNamespaces()->willReturn(array_keys($namespaces));
        foreach ($namespaces as $namespace => $paths) {
            $resolver->getTemplatePath($namespace)->willReturn($paths);
        }

        $aggregate = new AggregateResolver();
        $aggregate->attach($resolver->reveal());
        $mustache = new Mustache($aggregate);

        $template = new MustacheTemplate($mustache);
        $paths = $template->getPaths();
        foreach ($paths as $path) {
            $namespace = $path->getNamespace();
            $this->assertArrayHasKey($namespace, $namespaces);
            $this->assertContains($path->getPath(), $namespaces[$namespace]);
        }
    }

    public function testRenderShouldProxyToMustacheRenderMethod()
    {
        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', ['var' => 'value'])->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $this->assertEquals('RENDERED', $template->render('foo::bar', ['var' => 'value']));
    }

    public function testRendersGlobalDefaultParameters()
    {
        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', ['var' => 'value'])->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'var', 'value');
        $this->assertEquals('RENDERED', $template->render('foo::bar'));
    }

    public function testRendersPerTemplateDefaultParameters()
    {
        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', ['var' => 'value'])->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam('foo::bar', 'var', 'value');
        $this->assertEquals('RENDERED', $template->render('foo::bar'));
    }

    public function testDoesNotRenderPerTemplateDefaultParametersForDifferentTemplates()
    {
        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('bar::baz', [])->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam('foo::bar', 'var', 'value');

        // Note: rendering different template than one we added a default param to!
        $this->assertEquals('RENDERED', $template->render('bar::baz'));
    }

    public function testTemplateSpecificParametersHavePrecedenceOverGlobalParameters()
    {
        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', ['var' => 'VALUE'])->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'var', 'value');
        $template->addDefaultParam('foo::bar', 'var', 'VALUE');

        $this->assertEquals('RENDERED', $template->render('foo::bar'));
    }

    public function testParametersPassedToRenderHavePrecedenceOverTemplateSpecificParameters()
    {
        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', ['var' => 'VALUE'])->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam('foo::bar', 'var', 'value');

        $this->assertEquals('RENDERED', $template->render('foo::bar', ['var' => 'VALUE']));
    }

    public function testParametersPassedToRenderHavePrecedenceOverGlobalParameters()
    {
        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', ['var' => 'VALUE'])->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'var', 'value');

        $this->assertEquals('RENDERED', $template->render('foo::bar', ['var' => 'VALUE']));
    }

    public function testCanUseDefaultParamsWithViewModels()
    {
        $vars = (object) [
            'foo' => 'bar',
        ];

        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', $vars)->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'var', 'value');

        $this->assertEquals('RENDERED', $template->render('foo::bar', $vars));
        $this->assertAttributeEquals('value', 'var', $vars);
    }

    public function testCanRegisterListenersForMergingDefaultParameters()
    {
        $vars = new ArrayObject([
            'foo' => 'bar',
        ]);

        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', $vars)->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'var', 'value');
        $template->addDefaultParam('foo::bar', 'var2', 'value2');
        $template->attachParamListener(function ($vars, array $defaults) {
            if (! $vars instanceof ArrayObject) {
                return;
            }
            $vars['defaults'] = $defaults;
            return $vars;
        });

        $this->assertEquals('RENDERED', $template->render('foo::bar', $vars));
        $this->assertEquals([
            'var'  => 'value',
            'var2' => 'value2',
        ], $vars['defaults']);
    }

    public function testDefaultParamListenersAreUsedIfParamListenerReturnsEmpty()
    {
        $vars = new ArrayObject([
            'foo' => 'bar',
        ], ArrayObject::ARRAY_AS_PROPS);

        $resolver  = $this->prophesize(DefaultResolver::class);
        $aggregate = $this->prophesize(AggregateResolver::class);
        $aggregate->hasType(DefaultResolver::class)->willReturn(true);
        $aggregate->fetchByType(DefaultResolver::class)->willReturn($resolver->reveal());

        $mustache = $this->prophesize(Mustache::class);
        $mustache->getResolver()->willReturn($aggregate->reveal());
        $mustache->render('foo::bar', $vars)->willReturn('RENDERED');

        $template = new MustacheTemplate($mustache->reveal());
        $template->addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'var', 'value');
        $template->addDefaultParam('foo::bar', 'var2', 'value2');
        $template->attachParamListener(function ($vars, array $defaults) {
            return;
        });

        $this->assertEquals('RENDERED', $template->render('foo::bar', $vars));
        $this->assertAttributeEquals('value', 'var', $vars);
        $this->assertAttributeEquals('value2', 'var2', $vars);
    }
}
