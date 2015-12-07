# The URI Helper

Generating URIs based on established routes is a common requirement for
templates. This can be handled in one of two ways:

- The view model can compose a utility for generating URIs, and then methods can
  proxy to that utility to generate the URI. *This approach requires that the
  router be injected into the view model, and proxy methods created for each URI
  you need to generate.*
- The view model could compose a [higher order section](http://phly-mustache.readthedocs.org/en/latest/syntax/#higher-order-sections)
  that returns a function that will render a URI based on the text. This
  approach is generic, but requires a syntax for describing the URI to generate.

This package ships with `Phly\Expressive\Mustache\UriHelper`, which provides a
higher order function for generating URIs based on route names. It expects a
JSON string describing an object with minimally a "name" member, and optionally
an "options" object with substitutions to provide when generating the URI.

```mustache
<p>
    Make sure you <a href="{{#uri}}{"name":"documentation"}{{/uri}}">
    read the documentation</a>.
</p>

<p>
    Though sometimes you will <a href="{{#uri}}{"name":"resource","options":{"id":"sha1"}}{{/uri}}">
    link to specific items.</a>
</p>
```

While verbose, the approach gives you flexibility in generating URIs in your
template, particularly if you'll be generating many of them.

The option values can *also* include substitutions, giving you more power:

```mustache
<p>
    I might want to <a href="{{#uri}}{"name":"user","options":{"username":"{{user}}"}}{{/uri}}">
    link to dynamic user.</a>
</p>
```

In the above example, `{{username}}` will be interpreted as a variable, and expanded
as such. This approach allows you to generate URIs based on other variables in
the view model!

## Registered by default

The `UriHelper` is registered as a global template [default parameter](default-params.md),
under the name "uri". If you do not want it to be named as such, or want to
prevent its registration with specific payloads, you can use a parameter
listener. These generally work best when use named classes for view models, or
if there are specific variable names or combinations present that you can
identify.

```php
$renderer->addParamListener(function ($vars, array $defaults) {
    if (! $vars instanceof BlogEntry) {
        // This listener is only relevant to BlogEntry instances.
        return;
    }

    foreach ($defaults as $key => $value) {
        if ($key === 'uri') {
            // Put the "uri" helper into a different property:
            $vars->uriHelper = $value;
            continue;
        }
        $vars->{$key} = $value;
    }

    return $vars;
});
```
