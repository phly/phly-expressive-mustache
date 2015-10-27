<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace Phly\Expressive\Mustache;

use Phly\Mustache\Mustache;
use Phly\Mustache\Resolver\AggregateResolver;
use Phly\Mustache\Resolver\DefaultResolver;
use Zend\Expressive\Template\DefaultParamsTrait;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Template\TemplatePath;
use Zend\Stdlib\ArrayUtils;

/**
 * Provides a phly-mustache TemplateRendererInterface adapter for Expressive.
 */
class MustacheTemplate implements TemplateRendererInterface
{
    use DefaultParamsTrait;

    /**
     * @var callable[]
     */
    private $paramListeners = [];

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

        // Register default parameter listeners.
        $this->paramListeners[] = function ($params, array $defaults) {
            return $this->mergeArrayParams($params, $defaults);
        };

        $this->paramListeners[] = function ($params, array $defaults) {
            return $this->mergeObjectParams($params, $defaults);
        };
    }

    /**
     * Attach a listener for merging default values.
     *
     * When merging default values, behavior may need to vary based on the
     * variables passed to render(); e.g., view model objects may need to
     * retain certain behavior in order to work correctly.
     *
     * To facilitate this, we provide the ability to attach parameter
     * listeners; these receive the values passed to render(), as well as
     * the default values, allowing a listener to decide if it can merge them
     * for you:
     *
     * <code>
     * function ($vars, array $defaults)
     * </code>
     *
     * If the listener cannot handle them, it should return void (null);
     * otherwise, it should return an array or object representing the merged
     * values.
     *
     * By default, we register two listeners, one for injecting an object with
     * public properties based on the default parameters, and another for
     * merging array parameters.
     *
     * @param callable $listener
     */
    public function attachParamListener(callable $listener)
    {
        $this->paramListeners[] = $listener;
    }

    /**
     * {@inheritDoc}
     */
    public function render($name, $vars = [])
    {
        $vars = $this->mergeParams($name, $vars);
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

    /**
     * Merge passed and default parameters.
     *
     * Retrieves global and template-specific default parameters, and then triggers
     * each parameter listener with the provided $vars and the defaults, until one
     * returns a non-null, non-scalar result that is not identical to $vars.
     *
     * If none returns such a result, the provided $vars are returned unmodified.
     *
     * @param $string $name Template name.
     * @param array|object $vars Passed template variables.
     * @return array|object
     */
    private function mergeParams($name, $vars)
    {
        $globalDefaults = isset($this->defaultParams[TemplateRendererInterface::TEMPLATE_ALL])
            ? $this->defaultParams[TemplateRendererInterface::TEMPLATE_ALL]
            : [];

        $templateDefaults = isset($this->defaultParams[$name])
            ? $this->defaultParams[$name]
            : [];

        $defaults = ArrayUtils::merge($globalDefaults, $templateDefaults);

        foreach (array_reverse($this->paramListeners) as $listener) {
            $result = $listener($vars, $defaults);
            if (null !== $result && ! is_scalar($result)) {
                return $result;
            }
        }

        return $vars;
    }

    /**
     * Merge default parameters with provided parameters.
     *
     * Returns $params verbatim if they are not an array.
     *
     * When merging, provided parameters have precedence.
     *
     * @param mixed $params
     * @param array $defaults
     * @return null|array|mixed
     */
    private function mergeArrayParams($params, array $defaults)
    {
        if (! is_array($params)) {
            return;
        }

        return ArrayUtils::merge($defaults, $params);
    }

    /**
     * Merge defaults with a view model object.
     *
     * Returns $params verbatim if they are not an object.
     *
     * When merging, provided parameters have precedence.
     *
     * @param mixed $params
     * @param array $defaults
     * @return object|mixed
     */
    private function mergeObjectParams($params, array $defaults)
    {
        if (! is_object($params)) {
            return;
        }

        foreach ($defaults as $key => $value) {
            if (! isset($params->{$key}) && ! method_exists($params, $key)) {
                $params->{$key} = $value;
            }
        }

        return $params;
    }
}
