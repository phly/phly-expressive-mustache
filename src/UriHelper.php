<?php
/**
 * @copyright  Copyright (c) 2015 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace Phly\Expressive\Mustache;

use RuntimeException;
use Zend\Expressive\Helper\UrlHelper;

/**
 * Helper to compose in views that require URI generation.
 *
 * Typically, compose this as the "uri" variable.
 */
class UriHelper
{
    private $helper;

    /**
     * Inject the UrlHelper to use for URI generation.
     *
     * @param UrlHelper $helper
     */
    public function __construct(UrlHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Higher-order section for URI generation in templates.
     *
     * The function takes the text provided, and passes it to `json_decode()`;
     * if an array containing the key `name` is returned, it passes the data
     * along to the composed `UrlHelper` instance's `generate()` method, otherwise
     * returning the original text verbatim.
     *
     * If an `options` key is also present in the data, and an array, that
     * information is passed to the `generateUri()` method's second argument.
     *
     * For consumers:
     *
     * <code>
     * <a href="{{#uri}}{"name": "route.name", "options": {"id": {{id}}}}{{/uri}}">
     *     Link text
     * </a>
     * </code>
     *
     * @return callable
     */
    public function __invoke()
    {
        return function ($text, $renderer) {
            // Decode the text .
            $data = json_decode($text, true);

            // Now, can we can use it?
            if (! is_array($data) || ! isset($data['name'])) {
                return $text;
            }

            $route   = $data['name'];
            $options = $this->parseOptions($data, $renderer);
            $uri     = $this->helper->generate($route, $options);

            // Bug in URI generation; optional segments are not being stripped
            // in FastRoute.
            return str_replace('[/]', '', $uri);
        };
    }

    /**
     * Parse options
     *
     * It can be useful to use view data when providing options (e.g., to
     * inject an identifier into a generated URI); this method takes the
     * options, checking each value for templated items, and, when found,
     * passing them through the renderer.
     *
     * Higher order functions are passed the renderer as a callable, which
     * accepts a string and returns a string.
     *
     * @param array $data
     * @param callable $renderer
     * @return array
     */
    private function parseOptions(array $data, callable $renderer)
    {
        if (! isset($data['options']) || ! is_array($data['options'])) {
            return [];
        }

        $options = $data['options'];
        foreach ($options as $key => $value) {
            if (preg_match('/\{\{[^{]+\}\}/', $value)) {
                $options[$key] = $renderer($value);
            }
        }

        return $options;
    }
}
