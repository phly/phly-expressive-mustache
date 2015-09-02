<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace Phly\Expressive\Mustache;

use Interop\Container\ContainerInterface;
use Phly\Mustache\Lexer;
use Phly\Mustache\Mustache;
use Phly\Mustache\Pragma;
use Phly\Mustache\Renderer;
use Phly\Mustache\Resolver;
use Zend\Escaper\Escaper;

/**
 * Factory for use with container-interop for creating MustacheTemplate instances from configuration.
 */
class MustacheTemplateFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('Config') ? $container->get('Config') : [];
        $config = isset($config['phly-mustache']) ? $config['phly-mustache'] : [];

        $mustache = new Mustache($this->createResolver($config, $container));

        $this->injectLexer($config, $mustache, $container);
        $this->injectRenderer($config, $mustache, $container);
        $this->injectPragmas($config, $mustache->getPragmas(), $container);

        return new MustacheTemplate($mustache);
    }

    /**
     * Create the aggregate resolver to use with the Mustache instance.
     *
     * @param array $config phly-mustache configuration.
     * @param ContainerInterface $container
     * @return Resolver\AggregateResolver
     */
    private function createResolver(array $config, ContainerInterface $container)
    {
        $resolvers = [];
        if (isset($config['resolvers'])) {
            $resolvers = $this->createResolvers($config['resolvers'], $container);
        }

        $aggregate = new Resolver\AggregateResolver();
        foreach ($resolvers as $resolver) {
            $aggregate->attach($resolver);
        }

        if ($aggregate->hasType(Resolver\DefaultResolver::class)) {
            $defaultResolver = $aggregate->fetchByType(Resolver\DefaultResolver::class);
        } else {
            $defaultResolver = new Resolver\DefaultResolver();
            $aggregate->attach($defaultResolver, 0);
        }

        if (isset($config['paths']) && is_array($config['paths'])) {
            $this->injectResolverPaths($config['paths'], $defaultResolver);
        }

        if (isset($config['suffix']) && is_string($config['suffix']) && ! empty($config['suffix'])) {
            $defaultResolver->setSuffix($config['suffix']);
        }

        if (isset($config['separator']) && is_string($config['separator']) && ! empty($config['separator'])) {
            $defaultResolver->setSeparator($config['separator']);
        }

        return $aggregate;
    }

    /**
     * Create resolvers from configuration.
     *
     * @param array $config List of resolvers to use.
     * @param ContainerInterface $container
     * @return ResolverInterface[]
     */
    private function createResolvers(array $config, ContainerInterface $container)
    {
        $resolvers = [];

        foreach ($config as $resolver) {
            if ($resolver instanceof Resolver\ResolverInterface) {
                $resolvers[] = $resolver;
                continue;
            }

            if (! is_string($resolver)) {
                continue;
            }

            if ($container->has($resolver)) {
                $resolvers[] = $container->get($resolver);
                continue;
            }

            if (class_exists($resolver)) {
                $resolvers[] = new $resolver();
                continue;
            }
        }

        return $resolvers;
    }

    /**
     * Inject paths into the default resolver.
     *
     * @param array $config Path configuration
     * @param Resolver\DefaultResolver $resolver
     */
    private function injectResolverPaths($config, Resolver\DefaultResolver $resolver)
    {
        foreach ($config as $index => $paths) {
            $namespace = is_numeric($index) ? null : $index;
            foreach ((array) $paths as $path) {
                $resolver->addTemplatePath($path, $namespace);
            }
        }
    }

    /**
     * Injects configured pragmas into the pragma collection.
     *
     * @param array $config
     * @param Pragma\PragmaCollection $pragmas
     * @param ContainerInterface $container
     */
    private function injectPragmas(array $config, Pragma\PragmaCollection $pragmas, ContainerInterface $container)
    {
        if (! isset($config['pragmas']) || ! is_array($config['pragmas'])) {
            return;
        }

        foreach ($config['pragmas'] as $pragma) {
            if ($pragma instanceof Pragma\PragmaInterface) {
                $pragmas->add($pragma);
                continue;
            }

            if (! is_string($pragma)) {
                continue;
            }

            if ($container->has($pragma)) {
                $pragmas->add($container->get($pragma));
                continue;
            }

            if (class_exists($pragma)) {
                $pragmas->add(new $pragma());
                continue;
            }
        }
    }

    /**
     * Inject the lexer, if needed, and potentially set the 'disable strip whitespace' flag.
     *
     * @param array $config
     * @param Mustache $mustache
     * @param ContainerInterface $container
     */
    private function injectLexer(array $config, Mustache $mustache, ContainerInterface $container)
    {
        if (isset($config['lexer'])) {
            if (is_string($config['lexer']) && $container->has($config['lexer'])) {
                // Assume fully configured if pulled from container.
                $mustache->setLexer($container->get($config['lexer']));
                return;
            }

            if ($config['lexer'] instanceof Lexer) {
                $mustache->setLexer($config['lexer']);
            }

            if (is_string($config['lexer']) && class_exists($config['lexer'])) {
                $mustache->setLexer(new $config['lexer']());
            }
        }

        if (! array_key_exists('disable_strip_whitespace', $config)) {
            return;
        }

        $mustache->getLexer()->disableStripWhitespace((bool) $config['disable_strip_whitespace']);
    }

    /**
     * Inject the renderer, if needed, and potentially the escaper.
     *
     * @param array $config
     * @param Mustache $mustache
     * @param ContainerInterface $container
     */
    private function injectRenderer(array $config, Mustache $mustache, ContainerInterface $container)
    {
        if (isset($config['renderer'])) {
            if (is_string($config['renderer']) && $container->has($config['renderer'])) {
                // Assume fully configured at this point.
                $mustache->setRenderer($container->get($config['renderer']));
                return;
            }

            if ($config['renderer'] instanceof Renderer) {
                $mustache->setRenderer($config['renderer']);
            }

            if (is_string($config['renderer']) && class_exists($config['renderer'])) {
                $mustache->setRenderer(new $config['renderer']());
            }
        }

        if (! isset($config['escaper'])) {
            return;
        }

        if ($config['escaper'] instanceof Escaper) {
            $mustache->getRenderer()->setEscaper($config['escaper']);
            return;
        }

        if (! is_string($config['escaper'])) {
            return;
        }

        if ($container->has($config['escaper'])) {
            $mustache->getRenderer()->setEscaper($container->get($config['escaper']));
            return;
        }

        if (class_exist($config['escaper'])) {
            $mustache->getRenderer()->setEscaper(new $config['escaper']());
            return;
        }
    }
}
