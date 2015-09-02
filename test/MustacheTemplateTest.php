<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace PhlyTest\Expressive\Mustache;

use Phly\Expressive\Mustache\MustacheTemplate;
use Phly\Mustache\Mustache;
use Phly\Mustache\Resolver\AggregateResolver;
use Phly\Mustache\Resolver\DefaultResolver;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Expressive\Template\TemplateInterface;
use Zend\Expressive\Template\TemplatePath;

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
}
