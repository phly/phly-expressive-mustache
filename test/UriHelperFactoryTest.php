<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace PhlyTest\Expressive\Mustache;

use Phly\Expressive\Mustache\UriHelper;
use Phly\Expressive\Mustache\UriHelperFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Expressive\Helper\UrlHelper;

class UriHelperFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new UriHelperFactory();
    }

    public function testReturnsAUriHelperInstanceWhenTheUrlHelperIsPresent()
    {
        $factory = $this->factory;
        $baseHelper = $this->prophesize(UrlHelper::class);
        $this->container->get(UrlHelper::class)->willReturn($baseHelper->reveal());
        $this->assertInstanceOf(UriHelper::class, $factory($this->container->reveal()));
    }

    public function testRaisesExceptionIfTheUrlHelperIsNotPresent()
    {
        $factory = $this->factory;
        $this->container->get(UrlHelper::class)->willThrow(RuntimeException::class);
        $this->expectException(RuntimeException::class);
        $factory($this->container->reveal());
    }
}
