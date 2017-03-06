<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace PhlyTest\Expressive\Mustache;

use Interop\Container\ContainerInterface;
use Phly\Expressive\Mustache\MustacheTemplate;
use Phly\Expressive\Mustache\MustacheTemplateFactory;
use Phly\Expressive\Mustache\UriHelper;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionProperty;
use Zend\Expressive\Helper\UrlHelper;

class MustacheTemplateFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory   = new MustacheTemplateFactory();
    }

    public function injectContainer($name, $service)
    {
        $service = $service instanceof ObjectProphecy ? $service->reveal() : $service;
        $this->container->has($name)->willReturn(true);
        $this->container->get($name)->willReturn($service);
    }

    public function testFactoryCanCreateInstanceWithoutConfiguration()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $result = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(MustacheTemplate::class, $result);
    }

    public function testFactoryInjectsUriHelperAsGlobalDefaultUriParameter()
    {
        $this->injectContainer(UrlHelper::class, $this->prophesize(UrlHelper::class));

        $this->container->has('config')->willReturn(false);
        $mustache = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(MustacheTemplate::class, $mustache);

        $r = new ReflectionProperty($mustache, 'defaultParams');
        $r->setAccessible(true);
        $defaultParams = $r->getValue($mustache);

        $this->assertArrayHasKey('*', $defaultParams);
        $params = $defaultParams['*'];
        $this->assertArrayHasKey('uri', $params, var_export(array_keys($params), 1));
        $this->assertInstanceOf(UriHelper::class, $params['uri']);
    }
}
