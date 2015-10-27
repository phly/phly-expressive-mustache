# Default Params

Since zend-expressive 0.5, the `TemplateRendererInterface` has provided the
ability to specify default parameters to use when rendering, on both a global
and a per-template level.

In rendering engines such as Plates and Twig, this is generally a simple
proposition, as they only allow passing arrays of parameters when rendering.
However, for Mustache, where creation of view models is typical, this requires a
little special handling.

By default, we provide strategies for the following:

- If an array of values is passed to `render()`, these are merged with the
  default values, if any, with the passed values having precedence.
- If an value object is passed to `render()`, it will attempt to inject
  default values as object properties, assuming:
  - the property does not already exist.
  - a method of the same name does not already exist.

Since view models often contain behavior, and may not be conducive to the above,
we also allow you to register your own strategies, using the method
`addParamListener()`. This method accepts a callable, which should have the
following signature:

```php
function ($params, array $defaults)
```

If it **cannot** handle the provided `$params`, it should return void/null,
which will allow the next listener in the stack to execute. Otherwise, it should
attempt to merge the values, and return a value representing the merged
structure. The first listener to return a non-void/null/scalar value will halt
execution of the stack, and the value it returns will be used when rendering.

As an example, let's consider the following view model:

```php
class User
{
    public $id;
    public $fullname;
    public $email;
    public $uri;

    public function merge(array $values)
    {
        if (isset($values['given_name']) && isset($values['surname'])) {
            $this->fullname = sprintf('%s %s', $values['given_name], $values['surname']);
        }
        if (isset($values['url'])) {
            $this->uri = $values['url'];
        }
    }
}
```

In this case, we only want to merge specific default values, and ignore the
rest. As such, we might register the following with the Mustache renderer
implementation:

```php
$renderer->addParamListener(function ($vars, array $defaults) {
    if (! $vars instanceof User) {
        return;
    }
    $vars->merge($defaults);
    return $vars;
});
```

At this point, whenever a `User` instance is discovered, it will use the above
listener to merge values into the view model.
