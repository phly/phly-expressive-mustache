<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace Phly\Expressive\Mustache;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Helper\UrlHelper;

class UriHelperFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new UriHelper($container->get(UrlHelper::class));
    }
}
