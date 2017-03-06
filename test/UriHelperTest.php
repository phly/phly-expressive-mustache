<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace PhlyTest\Expressive\Mustache;

use Phly\Expressive\Mustache\UriHelper;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router\Exception\RuntimeException;

class UriHelperTest extends TestCase
{
    public function setUp()
    {
        $this->renderer = function () {
        };
        $this->baseHelper = $this->prophesize(UrlHelper::class);
    }

    public function createHelper()
    {
        return new UriHelper($this->baseHelper->reveal());
    }

    public function nonArrayJsonPayloads()
    {
        return [
            'null' => ['null'],
            'true' => ['true'],
            'false' => ['false'],
            'zero' => ['0'],
            'int' => ['1'],
            'zero-float' => ['0.0'],
            'float' => ['1.1'],
            'string' => ['"name"'],
        ];
    }

    /**
     * @dataProvider nonArrayJsonPayloads
     */
    public function testNonArrayJsonDataReturnsDataVerbatim($data)
    {
        $helper = $this->createHelper();
        $function = $helper();
        $this->assertSame($data, $function($data, $this->renderer));
    }

    public function jsonArrayPayloadsWithoutNames()
    {
        return [
            'indexed-array' => ['["foo", "bar"]'],
            'object' => ['{"foo": "bar"}'],
        ];
    }

    /**
     * @dataProvider jsonArrayPayloadsWithoutNames
     */
    public function testArrayDataMissingNameElementReturnsDataVerbatim($data)
    {
        $helper = $this->createHelper();
        $function = $helper();
        $this->assertSame($data, $function($data, $this->renderer));
    }

    public function testReturnsUrlForMatchingRouteWithoutOptions()
    {
        $this->baseHelper->generate('resource', [])->willReturn('/resource');
        $helper = $this->createHelper();
        $function = $helper();
        $url = $function('{"name":"resource"}', $this->renderer);
        $this->assertEquals('/resource', $url);
    }

    public function testReturnsUrlForMatchingRouteWithOptionsThatContainNoExpansions()
    {
        $this->baseHelper->generate('resource', ['id' => 'sha1'])->willReturn('/resource/sha1');
        $helper = $this->createHelper();
        $function = $helper();
        $url = $function('{"name":"resource","options":{"id":"sha1"}}', $this->renderer);
        $this->assertEquals('/resource/sha1', $url);
    }

    public function testReturnsUrlForMatchingRouteWithOptionsThatContainExpansions()
    {
        $this->baseHelper->generate('user', ['user' => 'mwop'])->willReturn('/user/mwop');
        $renderer = function ($value) {
            $this->assertEquals('{{user}}', $value);
            return 'mwop';
        };

        $helper = $this->createHelper();
        $function = $helper();
        $url = $function('{"name":"user","options":{"user":"{{user}}"}}', $renderer);
        $this->assertEquals('/user/mwop', $url);
    }

    public function testRaisesExceptionIfRouteDoesNotExist()
    {
        $this->baseHelper->generate('resource', [])->willThrow(RuntimeException::class);
        $helper = $this->createHelper();
        $function = $helper();
        $this->expectException(RuntimeException::class);
        $url = $function('{"name":"resource"}', $this->renderer);
    }
}
