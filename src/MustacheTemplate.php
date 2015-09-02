<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace Phly\Expressive\Mustache;

use Phly\Mustache\Mustache;
use Phly\Mustache\Resolver\AggregateResolver;
use Phly\Mustache\Resolver\DefaultResolver;
use Zend\Expressive\Template\TemplateInterface;
use Zend\Expressive\Template\TemplatePath;

/**
 * Provides a phly-mustache TemplateInterface adapter for Expressive.
 */
class MustacheTemplate implements TemplateInterface
{
    /**
     * @var Mustache
     */
    private $renderer;

    /**
     * @var DefaultResolver
     */
    private $resolver;

    /**
     * Constructor.
     *
     * Composes the Mustache instance. If the AggregateResolver composed by
     * Mustache does not compose a DefaultResolver, one is created and attached
     * to the Aggregate.
     *
     * The first DefaultResolver encountered in the Aggregate is then used as
     * the internal resolver for attaching paths.
     *
     * @param Mustache $renderer
     */
    public function __construct(Mustache $renderer)
    {
        $this->renderer = $renderer;

        $resolver = $renderer->getResolver();

        if (! $resolver->hasType(DefaultResolver::class)) {
            $resolver->attach($this->createDefaultResolver(), 0);
        } else {
            $this->extractDefaultResolver($resolver);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function render($name, $vars = [])
    {
        return $this->renderer->render($name, $vars);
    }

    /**
     * {@inheritDoc}
     */
    public function addPath($path, $namespace = null)
    {
        $this->resolver->addTemplatePath($path, $namespace);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaths()
    {
        $resolver = $this->resolver;
        $paths    = [];

        foreach ($resolver->getNamespaces() as $namespace) {
            $namespace = ($namespace !== DefaultResolver::DEFAULT_NAMESPACE) ? $namespace : null;
            foreach ($resolver->getTemplatePath($namespace) as $path) {
                $paths[] = new TemplatePath($path, $namespace);
            }
        }

        return $paths;
    }

    /**
     * Creates and returns a DefaultResolver.
     *
     * @return DefaultResolver
     */
    private function createDefaultResolver()
    {
        $this->resolver = new DefaultResolver();
        return $this->resolver;
    }

    /**
     * Extract and compose the DefaultResolver found in an AggregateResolver.
     *
     * Also sets the internal $resolver property to the first found.
     *
     * @param AggregateResolver $aggregate
     */
    private function extractDefaultResolver(AggregateResolver $aggregate)
    {
        if ($this->resolver instanceof DefaultResolver) {
            return;
        }

        $resolver = $aggregate->fetchByType(DefaultResolver::class);
        if ($resolver instanceof AggregateResolver) {
            $queue    = $resolver->getIterator();
            $resolver = $queue->top();
        }

        $this->resolver = $resolver;
    }
}
