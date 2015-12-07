<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace PhlyTest\Expressive\Mustache;

use Interop\Container\ContainerInterface;
use Phly\Expressive\Mustache\MustacheTemplate;
use Phly\Expressive\Mustache\MustacheTemplateFactory;
use Phly\Mustache\Mustache;
use Phly\Mustache\Resolver\AggregateResolver;
use Phly\Mustache\Resolver\DefaultResolver;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Expressive\Template\TemplatePath;

class MustacheTemplateFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory   = new MustacheTemplateFactory();
    }

    public function testFactoryCanCreateInstanceWithoutConfiguration()
    {
        $this->container->has('Config')->willReturn(false);
        $result = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(MustacheTemplate::class, $result);
    }
}
